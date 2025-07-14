<?php

namespace dbgrid;

use Formbuilder\Formbuilder;
use database\DBTable;
use helper\Helper;
use Exception;
use Throwable;

/**
 * Version 1.3.10
 * Handles the logic for building, processing, and rendering CRUD forms.
 * This class encapsulates the complex 'form' method logic from a larger CRUD class.
 */
class FormHandler {
    private DbCrud $parentCrud;

    public bool $encodeIdentifier = false;          // identifier will be base64 encoded  
    public array $rows;                             // grid layout for form
    public array $subform;                          // store the controller + method to handle a subform
    public array $addFields;                        // fields to show during form add action
    public array $editFields;                       // fields to show during form edit action
    public array $readFields;                       // fields to show during form read action
    public array $callbackRules;                    // fields rules at form validation
    public mixed $callbackInsert;                   // replaces buildin insert
    public mixed $callbackUpdate;                   // replaces buildin update
    public mixed $callbackException;                // callback on exceptions
    public array $fieldValues;                      // the field initial values
    public array $fieldOnchange;                    // adds an ajax call to field onchange event
    public array $fieldOninput ;                    // adds an ajax call to field oninput event
    public array $fieldDataAttr;                    // addas data attributes to a field
    public array $constraints;                      // stores a list of fields which do have depending tables ( parent -> child)
    public array $linkedTable;                      // store the controller + method to link with a button in edit mode
    public array $requiredFields;                   // fields which are required on form
    public array $readonlyFields;                   // fields which are readonly on form
    public array $fieldPlaceholders;                // the field placeholders when editing form

    public function __construct(DbCrud $parentCrud) {
        $this->parentCrud = $parentCrud;
    }

    /**
     * The main entry point to handle the form, including rendering and submission processing.
     * This method orchestrates all the steps involved in displaying or processing a form.
     *
     * @param string $action The action context ('add', 'edit', 'show').
     * @param string $id The record ID (can be encoded).
     * @param string $msg An optional message to display on the form.
     * @param string $wrapper An optional CSS wrapper style for the form.
     * @return string The rendered form HTML, or an empty string if a redirect occurs.
     * @throws Exception If an onchange mapping cannot be encoded.
     */
    public function handle(string $action, string $id = '', string $msg = '', string $wrapper = ''): string {
        $linkId = $id;

        if ($this->encodeIdentifier && !empty($id)) {
            $id = $this->parentCrud->base64url_decode($id);
        }

        $fields = $this->determineFields($action);
        $search = $_REQUEST["search"] ?? '';
        $page = $_REQUEST['last_page'] ?? 1;
        $query = !empty($search) ? "?search=$search&last_page=$page" : "?last_page=$page";
        $gridTo = '/' . $this->parentCrud->get_uri('class');
        $backlink = "$gridTo/grid/{$page}{$query}";
        $deletelink = "$gridTo/delete/{$linkId}{$query}";
        $formAction = '/' . $this->parentCrud->get_uri('path') . $query;

        if (empty($wrapper)) {
            $wrapper = !empty($this->rows) ? 'bootstrap-inline' : 'bootstrap-h-sm';
        }

        $subformRequested = (htmlspecialchars($_REQUEST["subform"] ?? '') === 'true');
        $form = new Formbuilder($this->parentCrud->model()->name(), ['action' => $formAction, 'wrapper' => $wrapper]);
        $form->set_secrets(Helper::env('app_secret', 'empty_secret'), Helper::env('app_identifier','empty_identifier'));
        $disabledMainForm = $subformRequested === true ? 'disabled' : '';
        $form->fieldset_open('', $disabledMainForm);
        $form->date_format = $this->parentCrud->date_fmt;
        $form->time_format = $this->parentCrud->time_fmt;

        if (!empty($msg)) {
            $form->message($msg);
        }

        // === 2. Form Submission Handling ===
        if ($form->submitted() && isset($_POST["mainform-save"])) {
            $this->applyValidationRules($form, $fields); 
            $data = $form->validate(implode(',', $fields));
            
            if ($data === false) { 
                $form->message('Data cannot be saved. Please check for errors.');
            } else {
                // === 3. Form Validation Success ===
                if ($form->ok()) { 
                    $data = $this->transformSubmittedData($data, $fields);
                    try {
                        return $this->saveData($id, $data, $gridTo, $linkId, $backlink);
                    } catch (Throwable $th) {
                        $this->handleSaveException($th, $form);
                    }
                }
            }
        }

        // === 1. Form Rendering ===
        $rowData = !empty($id) ? $this->parentCrud->model()->find($id) : false;
        $this->renderFormElements($form, $action, $fields, $rowData);
        $form->html('<br>');
        $this->buildButtonBar($form, $action, $backlink, $deletelink, $linkId, $rowData, $gridTo);
        $form->fieldset_close();

        if (!empty($this->rows)) {
            $form->layout_grid($this->rows);
        }

        $subformHtml = '';

        if ($subformRequested && !empty($this->subform['callback'])) {
            $subformHtml = '<div>' . call_user_func($this->subform['callback'], $id) . '</div>';
        }

        return '<div id="dbc-container">' . $form->render() . $subformHtml . '</div>';
    }

    /**
     * Determines which set of fields to use based on the current action.
     *
     * @param string $action The action context ('add', 'edit', 'show').
     * @return array The array of field names relevant to the action.
     */
    private function determineFields(string $action): array {
        switch ($action) {
            case 'add':
                return $this->addFields;
            case 'edit':
                return $this->editFields;
            case 'show':
                return $this->readFields;
            default:
                return [];
        }
    }

    /**
     * Applies validation rules to the Formbuilder instance based on field types,
     * table metadata, and user-defined required fields/callbacks.
     *
     * @param Formbuilder $form The Formbuilder instance.
     * @param array $fields The array of field names to apply rules to.
     */
    private function applyValidationRules(Formbuilder $form, array $fields): void {
        foreach ($fields as $field) {
            $isTableRequired = ($this->parentCrud->model()->fields($field)['required'] ?? false);
            $isUserRequired = ($this->requiredFields[$field] ?? false);

            if ($isTableRequired || $isUserRequired) {
                $form->rule('required', $field);
            }

            $fieldType = $this->parentCrud->getFieldProperty($field, 'type');
            
            switch ($fieldType) {
                case 'integer':
                    $form->rule('integer', $field);
                    break;
                case 'numeric':
                    $form->rule('numeric', $field);
                    break;
                case 'date':
                case 'datetext':
                case 'datetime':
                    $form->rule('date', $field);
                    break;
                case 'timetext':
                    $form->rule('time', $field);
                    break;
                case 'relation':
                case 'search':
                    $constraint = $this->parentCrud->getFieldProperty($field, 'constraint');

                    if (! empty($constraint) ) {
                        $form->rule([$this, 'check_relation'], $field);
                    }

                    break;
            }
        }

        if (!empty($this->callbackRules)) {
            foreach ($this->callbackRules as $field => $rule) {
                $form->rule($rule, $field);
            }
        }
    }

    /**
     * Transforms submitted form data into a format suitable for database storage.
     * This includes converting dates/times to timestamps, handling checkboxes,
     * and resolving relation/search field values to foreign keys.
     *
     * @param array $data The submitted form data.
     * @param array $fields The fields that are relevant for the current form action.
     * @return array The transformed data array.
     */
    private function transformSubmittedData(array $data, array $fields): array {
        foreach ($data as $field => $value) {
            if (!in_array($field, $fields)) {
                continue;
            }

            $fieldType = $this->parentCrud->getFieldProperty($field, 'type');

            switch ($fieldType) {
                case 'date':
                case 'datetext':
                case 'datetime':
                    // Convert date/datetime strings to Unix timestamps for storage
                    $data[$field] = empty($value) ? null : strtotime($value);
                    break;
                case 'timetext':
                    // Convert time strings to seconds from midnight for storage
                    $data[$field] = empty($value) ? null : strtotime($value) - strtotime('TODAY');
                    break;
                case 'checkbox':
                    // Map checkbox value (null if unchecked, or a string if checked)
                    // to the specified true/false values from field config.
                    $values = explode(',', $this->parentCrud->getFieldProperty($field, 'values'));

                    if ( $value === null )
                        $data[$field] = $values[0];
                    else
                        $data[$field] = $values[1];
                    
                    break;
                case 'relation':
                case 'search':
                    $relTable = $this->parentCrud->getFieldProperty($field, 'rel_table');
                    $relField = $this->parentCrud->getFieldProperty($field, 'rel_field');
                    $relationTable = new DBTable($relTable);
                    $relKey = $relationTable->primaryKey()[0];
                    $foundValue = $relationTable->where($relField, html_entity_decode($value, ENT_QUOTES | ENT_HTML5))->findColumn($relKey);
                    $data[$field] = $foundValue[0] ?? null;
                    break;
                case 'grid':
                    $data[$field] = json_encode($value);
                    break;
            }
        }
        return $data;
    }

    /**
     * Saves the processed data to the database (either inserts a new row or updates an existing one).
     * Handles redirection after successful save operations.
     *
     * @param string $id The decoded record ID (empty for new records).
     * @param array $data The data array prepared for database storage.
     * @param string $gridTo The base URL for the grid controller.
     * @param string $linkId The original encoded record ID (used for linked table redirection).
     * @param string $backlink The URL to redirect back to the grid.
     * @return string An empty string, indic`a`ting that a redirect has occurred.
     * @throws Throwable If a database constraint or other error occurs during insert/update.
     */
    private function saveData(string $id, array $data, string $gridTo, string $linkId, string $backlink): string {
        // just exit if there are no data added/modified, this can heppend if there a no required fields and the id is auto generated
        if (empty(array_filter($data))) {
            Helper::redirect($backlink);
            return ''; 
        }

        if (empty($id)) {
            if (isset($this->callbackInsert)) {
                call_user_func($this->callbackInsert, $data);
            } else {
                $this->parentCrud->model()->insert($data);
            }
        } else {
            if (isset($this->callbackUpdate)) {
                call_user_func($this->callbackUpdate, $id, $data);
            } else {
                $this->parentCrud->model()->update($id, $data); 
            }
        }

        if (empty($id) && !empty($this->linkedTable)) {
            $newId = $data[$this->parentCrud->get_primary_key()] ?? ''; 

            if (empty($newId)) { 
                $newId = $this->parentCrud->model()->database()->lastInsertId($this->parentCrud->model()->name());
            }

            if ($newId !== false) {
                $editlink = "$gridTo/edit/$newId";
                Helper::redirect($editlink); 
                return '';
            }
        }

        Helper::redirect($backlink);
        return ''; 
    }

    /**
     * Handles exceptions that occur during the data saving process.
     * It can use a user-defined callback or display the exception message.
     *
     * @param Throwable $th The thrown exception object.
     * @param Formbuilder $form The Formbuilder instance to display messages on.
     */
    private function handleSaveException(Throwable $th, Formbuilder $form): void {
        if (!empty($this->callbackException)) {
            $result = call_user_func($this->callbackException, $th);

            if (is_string($result)) {
                $form->message($result);
            } else {
                $form->message($th->getMessage());
            }
        } else {
            $form->message($th->getMessage());
        }
    }

    /**
     * Renders all individual form elements (inputs, textareas, selects, etc.)
     * based on the defined fields and their types.
     *
     * @param Formbuilder $form The Formbuilder instance.
     * @param string $action The current action ('add', 'edit', 'show').
     * @param array $fields The array of field names to render.
     * @param array|false $rowData The existing record data, or false for new records.
     * @throws Exception If an onchange mapping cannot be encoded.
     */
    private function renderFormElements(Formbuilder $form, string $action, array $fields, $rowData): void {
        foreach ($fields as $field) {
            $meta = $this->parentCrud->getFieldProperties($field);
            $label = $this->parentCrud->get_field_title($field);
            $placeholder = $this->fieldPlaceholders[$field] ?? ''; 
            $value = $this->getFieldValue($field, $rowData);
            $readonly = $this->getFieldReadonlyStatus($field, $action);
            $data = $this->getFieldDataAttributes($field);

            $this->buildFormField($form, $field, $meta, $label, $value, $readonly, $placeholder, $data);
        }
    }

    /**
     * Retrieves the initial value for a form field.
     * It prioritizes existing row data, then default field values, and finally applies custom formatting callbacks.
     *
     * @param string $field The name of the field.
     * @param array|false $rowData The existing record data, or false.
     * @return string The formatted field value.
     */
    private function getFieldValue(string $field, $rowData): string {
        $value = '';
        
        if ($rowData !== false) {
            $value = $rowData[$field] ?? '';
        } elseif (isset($this->fieldValues[$field])) {
            $value = $this->fieldValues[$field];
        }

        if ( !is_null($this->parentCrud->get_callback_field($field)) ) {
            $value = call_user_func($this->parentCrud->get_callback_field($field), 'form', $value, null);
        }
        return (string) $value;
    }

    /**
     * Determines the readonly status for a given form field.
     * A field can be readonly if the action is 'show', if it's explicitly defined as readonly,
     * or if it's the primary key in 'edit' mode.
     *
     * @param string $field The name of the field.
     * @param string $action The current action ('add', 'edit', 'show').
     * @return string 'readonly' if the field should be readonly, otherwise an empty string.
     */
    private function getFieldReadonlyStatus(string $field, string $action): string {
        $readonly = '';
        
        if ($action === 'show') {
            $readonly = 'readonly';
        } elseif (isset($this->readonlyFields[$field])) {
            $readonly = 'readonly'; 
        }

        if ($field === $this->parentCrud->get_primary_key() && $action === 'edit') {
            $readonly = 'readonly';
        }

        return $readonly;
    }

    private function getFieldDataAttributes(string $field) : string {
        if ( empty($this->fieldDataAttr[$field]) )
            return '';

        $result = ' ';
        
        foreach ($this->fieldDataAttr[$field] as $key => $value) {
            $result .= "data-$key='$value' ";
        }

        return $result;
    }

    /**
     * Builds and adds a specific form field (e.g., text, date, select, textarea, etc.)
     * to the Formbuilder instance based on its defined type.
     *
     * @param Formbuilder $form The Formbuilder instance.
     * @param string $field The name of the field.
     * @param array $fieldMeta An associative array containing metadata for the field (e.g., 'type', 'rows', 'cols').
     * @param string $label The label for the form field.
     * @param string $value The initial value for the form field.
     * @param string $readonly The 'readonly' attribute string if applicable.
     * @param string $placeholder The 'placeholder' attribute string if applicable.
     * @param string $data The 'data' attributes string if applicable.
     * @throws Exception If a problem occurs during field processing (e.g., JSON encoding for grid).
     */
    private function buildFormField(Formbuilder $form, string $field,  array $fieldMeta, string $label, string $value, string $readonly, string $placeholder, string $data): void {
        $commonString = trim($readonly . (!empty($placeholder) ? ' placeholder="' . htmlspecialchars($placeholder) . '"' : '') . $data);
        $datepicker = $fieldMeta['datepicker'] ?? '';
        $onchange = (object) ($this->fieldOnchange[$field] ?? null);
        $oninput = (object) ($this->fieldOninput[$field] ?? null);
        $options = ['label' => $label, 'string' => $commonString, 'value' => $value, 'datepicker' => $datepicker];
        $fieldType = $fieldMeta['type'] ?? ''; 

        switch ($fieldType) {
            case 'date':
                if (!empty($value)) {
                    $value = date($this->parentCrud->date_fmt, (int) $value);
                }

                $form->date($field, array_merge($options, ['value' => $value]));
                break;
            case 'datetext':
                if (!empty($value)) {
                    $value = date($this->parentCrud->date_fmt, (int) $value);
                }

                $form->datetext($field, array_merge($options, ['format' => $this->parentCrud->date_fmt, 'value' => $value]));
                break;
            case 'timetext':
                if (!empty($value)) {
                    $value = gmdate($this->parentCrud->time_fmt, (int) $value);
                }

                $form->timetext($field, array_merge($options, ['format' => $this->parentCrud->time_fmt, 'value' => $value]));
                break;
            case 'datetime':
                if (!empty($value)) {
                    $value = date($this->parentCrud->date_fmt . ' ' . $this->parentCrud->date_fmt, (int) $value);
                }

                $form->datetime($field, array_merge($options, ['value' => $value]));
                break;
            case 'textarea':
                $rows = $fieldMeta['rows'] ?? null;
                $cols = $fieldMeta['cols'] ?? null;
                $form->textarea($field, array_merge($options, ['rows' => $rows, 'cols' => $cols]));
                break;
            case 'checkbox':
                $values = explode(',', $fieldMeta['values'] ?? '');
                $checked = (array_search($value, $values) == 1);
                $form->checkbox($field, ['label' => $label, 'checked' => $checked, 'string' => trim($readonly . $data)]);
                break;
            case 'select':
                $selectValues = $fieldMeta['values'] ?? []; 

                $form->when($oninput, fn($f) => $f->oninput($oninput->method))
                      ->when($onchange, fn($f) => $f->onchange($onchange->method, $onchange->mapping, $onchange->defer))
                      ->select($field, $selectValues, array_merge($options, ['value' => $value]));
                      
                break;
            case 'datalist':
                $datalistValues = $fieldMeta['values'] ?? [];

                $form->when($oninput, fn($f) => $f->oninput($oninput->method))
                      ->when($onchange, fn($f) => $f->onchange($onchange->method, $onchange->mapping, $onchange->defer))
                      ->datalist($field, $datalistValues, array_merge($options, ['value' => $value]));
                
                break;
            case 'relation':
                $relTable = $fieldMeta['rel_table'] ?? '';
                $relField = $fieldMeta['rel_field'] ?? '';
                $relationTable = new DBTable($relTable);
                $selectValues = $relationTable->orderby($relField)->findColumn($relField);
                array_unshift($selectValues, '');

                if (!empty($value)) {
                    $result = $relationTable->find($value);
                    $value = ($result !== false) ? ($result[$relField] ?? '') : '';
                } else {
                    $value = '';
                }

                $form->select($field, $selectValues, array_merge($options, ['value' => $value]));
                break;
            case 'search':
                $relTable = $fieldMeta['rel_table'] ?? '';
                $relField = $fieldMeta['rel_field'] ?? '';
                $relationTable = new DBTable($relTable);

                if (!empty($value)) {
                    $result = $relationTable->find($value);
                    $value = ($result !== false) ? ($result[$relField] ?? '') : '';
                } else {
                    $value = '';
                }

                $form->when($oninput, fn($f) => $f->oninput($oninput->method))
                      ->when($onchange, fn($f) => $f->onchange($onchange->method, $onchange->mapping, $onchange->defer))
                      ->search($field, array_merge($options, ['value' => $value]));
                    
                break;
            case 'grid':
                $gridValues = json_decode($value, true);

                if ($gridValues === false) {
                    $gridValues = [];
                }

                $rows = $fieldMeta['rows'] ?? 1;
                $cols = $fieldMeta['cols'] ?? 1;
                $gridString = [];

                if (!empty($readonly)) {
                    for ($r = 0; $r < $rows; $r++) {
                        for ($c = 0; $c < $cols; $c++) {
                            $gridString[$r][$c] = trim($readonly . (!empty($placeholder) ? ' placeholder="' . htmlspecialchars($placeholder) . '"' : '') . $data);
                        }
                    }
                }

                $form->grid($field, ['label' => $label, 'value' => $gridValues, 'rows' => $rows, 'cols' => $cols, 'string' => $gridString]);
                break;
        default:
            $form->when($oninput, fn($f) => $f->oninput($oninput->method))
                 ->when($onchange, fn($f) => $f->onchange($onchange->method, $onchange->mapping, $onchange->defer))
                 ->text($field, $options);
        
            break;
        }
    }

    /**
     * Builds and adds the button bar to the Formbuilder instance.
     * This includes "Save", "Back", "Delete", "Linked Table", and "Subform" buttons,
     * with appropriate labels, actions, and states (e.g., disabled for delete if constraints exist).
     *
     * @param Formbuilder $form The Formbuilder instance.
     * @param string $action The current action ('add', 'edit', 'show').
     * @param string $backlink The URL for the 'Back' button.
     * @param string $deletelink The URL for the 'Delete' button.
     * @param string $linkId The original encoded record ID for linked table/subform URLs.
     * @param array|false $rowData The existing record data, or false.
     */
    private function buildButtonBar(Formbuilder $form, string $action, string $backlink, string $deletelink, string $linkId, $rowData, string $gridTo): void {
        $btnBarNames = [];
        $btnBarValues = [];
        $btnBarOnclicks = [];
        $btnBarTypes = [];
        $btnBarStrings = [];

        if ($action === 'show') {
            $btnBarNames[] = 'mainform-back';
            $btnBarValues[] = $this->parentCrud->form_back;
            $btnBarOnclicks[] = $backlink;
            $btnBarTypes[] = 'button';
            $btnBarStrings[] = 'class="btn btn-secondary"';
        } else {
            $btnBarNames[] = 'mainform-save';
            $btnBarValues[] = $this->parentCrud->form_save;
            $btnBarOnclicks[] = '';
            $btnBarTypes[] = 'submit';
            $btnBarStrings[] = '';

            $btnBarNames[] = 'back';
            $btnBarValues[] = $this->parentCrud->form_back;
            $btnBarOnclicks[] = $backlink;
            $btnBarTypes[] = 'button';
            $btnBarStrings[] = 'class="btn btn-secondary"';        }

        if ($action !== 'add' && !empty($this->parentCrud->form_delete)) {
            $disabledDelete = '';

            if (!empty($this->constraints) && $rowData !== false) {
                foreach ($this->constraints as $field => $values) {
                    foreach ($values as $constraint) {
                        $dependingTable = $constraint['table'];
                        $dependingField = $constraint['field'];
                        $relationTable = new DBTable($dependingTable);
                        $value = $rowData[$field] ?? null; 
                        
                        if ($value !== null) {
                            $result = $relationTable->where($dependingField, $value)->limit(1)->count();

                            if ($result > 0) {
                                $disabledDelete = 'disabled';
                                break 2; 
                            }
                        }
                    }
                }
            }

            $btnBarNames[] = 'mainform-delete';
            $btnBarValues[] = $this->parentCrud->form_delete;
            $btnBarOnclicks[] = $deletelink;
            $btnBarTypes[] = 'button';
            $btnBarStrings[] = 'class="btn btn-danger ' . $disabledDelete . '"';

            if (!empty($this->linkedTable)) {
                $controller = $this->linkedTable['controller'];
                $value = $this->linkedTable['button_value'];
                $method = $this->linkedTable['method'];
                $btnBarNames[] = $controller;
                $btnBarValues[] = $value;
                $btnBarTypes[] = 'button';
                $btnBarStrings[] = 'class="btn btn-success"';
                $btnBarOnclicks[] = Helper::url() . "/$controller/$method/$linkId";
            }

            if (!empty($this->subform)) {
                $value = $this->subform['button_value'];
                $btnBarNames[] = 'btn-subform';
                $btnBarValues[] = $value;
                $btnBarTypes[] = 'button';
                $btnBarStrings[] = 'class="btn btn-success"';
                $btnBarOnclicks[] = "$gridTo/edit/$linkId?subform=true";
            }
        }

        $form->button_bar($btnBarNames, $btnBarValues, $btnBarOnclicks, $btnBarTypes, $btnBarStrings);
    }

    public function check_relation(string $value, string $field) : string {
        $rel_table = $this->parentCrud->getFieldProperty($field, 'rel_table');
        $rel_field = $this->parentCrud->getFieldProperty($field, 'rel_field');
        $table = new DBTable($rel_table);
        $rel_key = $table->primaryKey()[0];
        $value = $table->where($rel_field, html_entity_decode($value, ENT_QUOTES | ENT_HTML5))->findColumn($rel_key);

        if ( !isset($value[0]) )
            return 'pls enter a valid value from the proposed values';

        return '';
    }
}