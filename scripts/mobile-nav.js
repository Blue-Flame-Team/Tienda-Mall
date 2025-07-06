// منطق فتح وغلق قائمة الموبايل
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
const mobileNavDropdown = document.querySelector('.mobile-nav-dropdown');
const closeMobileNavBtn = document.querySelector('.close-mobile-nav');

function openMobileNav() {
  mobileNavOverlay.classList.add('open');
  mobileNavDropdown.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeMobileNav() {
  mobileNavOverlay.classList.remove('open');
  mobileNavDropdown.classList.remove('open');
  document.body.style.overflow = '';
}
if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileNav);
if (mobileNavOverlay) mobileNavOverlay.addEventListener('click', closeMobileNav);
if (closeMobileNavBtn) closeMobileNavBtn.addEventListener('click', closeMobileNav);
