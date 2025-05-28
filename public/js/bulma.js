function toggleNavbar(el) {
    const targetId = el.dataset.target;
    const target = document.getElementById(targetId);

    // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
    el.classList.toggle('is-active');
    target.classList.toggle('is-active');
}