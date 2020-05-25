var mainMenuBtn = document.getElementById('main-menu-btn');
var fixedMainMenu = document.getElementById('fixed-main-menu-container');

var secondaryNavBar = document.getElementById('secondary-navbar-fixed');
var secondaryMenuBtn = document.getElementById('secondary-menu-btn');
var fixedSidebarNav = document.getElementById('fixed-sidebar-nav-container');

mainMenuBtn.addEventListener('click', function (e) {
  e.preventDefault();
  toggleMainMenu();
});

secondaryMenuBtn.addEventListener('click', function (e) {
  e.preventDefault();
  toggleSidebarNav();
});

function toggleMainMenu() {
  mainMenuBtn.classList.toggle('active');
  fixedMainMenu.classList.toggle('active');
}

function toggleSidebarNav() {
  secondaryNavBar.classList.toggle('active');
  secondaryMenuBtn.classList.toggle('active');
  fixedSidebarNav.classList.toggle('active');
}
