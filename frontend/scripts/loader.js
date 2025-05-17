// Loader overlay logic
window.addEventListener('DOMContentLoaded', function() {
  // Animate the bird
  setTimeout(() => {
    const bird = document.querySelector('.loader-bird');
    if (bird) {
      bird.style.animation = 'bird-fly 1.9s cubic-bezier(.77,0,.18,1) 0.1s forwards';
      // Animate wings
      const leftWing = bird.querySelector('.bird-wing-left');
      const rightWing = bird.querySelector('.bird-wing-right');
      if (leftWing && rightWing) {
        leftWing.style.animation = 'wing-flap 0.44s ease-in-out infinite alternate';
        rightWing.style.animation = 'wing-flap 0.44s ease-in-out infinite alternate-reverse';
      }
    }
  }, 180);

  // 1. Create loader overlay
  const overlay = document.createElement('div');
  overlay.className = 'loader-overlay';
  overlay.innerHTML = `
    <div class="loader-3d-box" style="display: block;">
      <div class="face front">
        <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="bagBorderGradient" x1="0" y1="0" x2="80" y2="0" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#ff9800"/>
              <stop offset="100%" stop-color="#db4444"/>
            </linearGradient>
          </defs>
          <g class="bag-body">
            <rect x="18" y="28" width="44" height="38" rx="50" fill="#db4444" stroke="url(#bagBorderGradient)" stroke-width="3"/>
            <rect x="25" y="36" width="30" height="18" rx="50" fill="#fff" stroke="url(#bagBorderGradient)" stroke-width="2"/>
            <ellipse cx="40" cy="28" rx="12" ry="8" fill="#fff" stroke="#222" stroke-width="2"/>
            <path d="M30 28 Q40 12 50 28" stroke="#222" stroke-width="2.5" fill="none"/>
            <circle cx="34" cy="44" r="2.2" fill="#db4444"/>
            <circle cx="46" cy="44" r="2.2" fill="#db4444"/>
          </g>
          <g class="bag-handle">
            <path d="M30 28 Q40 16 50 28" stroke="#ff9800" stroke-width="2.5" fill="none"/>
          </g>
        </svg>
      </div>
      <div class="face back"></div>
      <div class="face left"></div>
      <div class="face right"></div>
      <div class="face top"></div>
      <div class="face bottom"></div>
      <div class="loader-reflection"></div>
    </div>
    <div class="loader-diagonal-transition" style="display: none;">
      <div class="diagonal left"></div>
      <div class="diagonal right"></div>
    </div>
    <div class="loader-final" style="display: none;">
      <div class="welcome-msg" style="opacity: 0;"><span class="welcome-emoji">😃</span>Welcome to Tienda!<br>استمتع بتجربة تسوق فريدة</div>
    </div>
  `;
  document.body.appendChild(overlay);

  // Animation sequence: 1) show box, 2) diagonal split, 3) show welcome, 4) hide loader
  setTimeout(() => {
    // Hide 3D loader box
    overlay.querySelector('.loader-3d-box').style.display = 'none';
    // Show diagonal split
    const diagTrans = overlay.querySelector('.loader-diagonal-transition');
    diagTrans.style.display = 'block';
    const left = diagTrans.querySelector('.diagonal.left');
    const right = diagTrans.querySelector('.diagonal.right');
    setTimeout(() => {
      left.classList.add('split-left');
      right.classList.add('split-right');
      setTimeout(() => {
        // Hide diagonals
        diagTrans.style.display = 'none';
        // Show welcome message
        const final = overlay.querySelector('.loader-final');
        final.style.display = 'block';
        setTimeout(() => {
          final.querySelector('.welcome-msg').style.opacity = 1;
          setTimeout(() => {
            overlay.style.opacity = 0;
            setTimeout(() => {
              overlay.remove();
            }, 700);
          }, 1200); // Welcome message duration
        }, 100);
      }, 800); // Diagonal split duration
    }, 100); // Start split after short delay
  }, 2000); // 3D box loader duration
});
