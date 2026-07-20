// ---- Loading screen ----
  (function(){
    const loader = document.getElementById('loader');
    function hideLoader(){
      document.body.classList.remove('loading');
      loader.classList.add('hide');
      setTimeout(() => { loader.style.display = 'none'; }, 650);
    }
    window.addEventListener('load', () => setTimeout(hideLoader, 1000));
    // Safety net in case 'load' is slow to fire
    setTimeout(hideLoader, 2500);
  })();

  // ---- Mouse glow (Apple-style) ----
  (function(){
    const glow = document.getElementById('mouse-glow');
    if(!glow || window.matchMedia('(hover: none)').matches) return;
    let raf = null;
    document.addEventListener('mousemove', (e) => {
      glow.classList.add('active');
      if(raf) return;
      raf = requestAnimationFrame(() => {
        glow.style.setProperty('--mx', e.clientX + 'px');
        glow.style.setProperty('--my', e.clientY + 'px');
        raf = null;
      });
    });
    document.addEventListener('mouseleave', () => glow.classList.remove('active'));
  })();

  // ---- Age & birthday countdown ----
  const birthDate = new Date(2007, 10, 15); // month index 10 = November

  function updateAgeAndCountdown(){
    const now = new Date();
    let age = now.getFullYear() - birthDate.getFullYear();
    const hasHadBirthdayThisYear =
      (now.getMonth() > birthDate.getMonth()) ||
      (now.getMonth() === birthDate.getMonth() && now.getDate() >= birthDate.getDate());
    if(!hasHadBirthdayThisYear) age -= 1;
    document.getElementById('age-value').textContent = age + ' yrs';

    let nextBday = new Date(now.getFullYear(), birthDate.getMonth(), birthDate.getDate());
    if(nextBday < now){ nextBday.setFullYear(nextBday.getFullYear() + 1); }
    const diff = nextBday - now;
    const days = Math.floor(diff / (1000*60*60*24));
    const hours = Math.floor((diff / (1000*60*60)) % 24);
    const mins = Math.floor((diff / (1000*60)) % 60);

    const cd = document.getElementById('countdown');
    cd.innerHTML = `
      <div class="cd-box"><div class="cd-num">${days}</div><div class="cd-label">Days</div></div>
      <div class="cd-box"><div class="cd-num">${hours}</div><div class="cd-label">Hrs</div></div>
      <div class="cd-box"><div class="cd-num">${mins}</div><div class="cd-label">Min</div></div>
    `;
  }
  updateAgeAndCountdown();
  setInterval(updateAgeAndCountdown, 200);

  // ---- NFC Card Popup ----
  (function(){
    const trigger   = document.getElementById('nfc-trigger');
    const overlay   = document.getElementById('nfc-overlay');
    const closeBtn  = document.getElementById('nfc-close');
    const card      = document.getElementById('nfc-card');
    const cardInner = document.getElementById('nfc-card-inner');
    const hintText  = document.getElementById('nfc-hint-text');

    cardInner.classList.toggle('flipped');
    let isOpen = false;

    function openModal(){
      isOpen = true;
      isFlipped = false;
      cardInner.classList.remove('flipped');
      hintText.textContent = 'Tap the card to flip';
      overlay.classList.remove('closing');
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeModal(){
      if(!isOpen) return;
      isOpen = false;
      overlay.classList.add('closing');
      overlay.classList.remove('open');
      document.body.style.overflow = '';
      setTimeout(() => overlay.classList.remove('closing'), 400);
    }

   function handleCardClick(e){
    e.stopPropagation();

    isFlipped = !isFlipped;

    cardInner.classList.toggle('flipped', isFlipped);

    hintText.textContent = isFlipped
        ? 'Tap the card to see the front'
        : 'Tap the card to flip';
}

    trigger.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    card.addEventListener('click', handleCardClick);

    // Clicking the blurred backdrop also closes it
    overlay.addEventListener('click', (e) => {
      if(e.target === overlay){ closeModal(); }
    });

    // Escape key closes it
    document.addEventListener('keydown', (e) => {
      if(e.key === 'Escape' && isOpen){ closeModal(); }
    });
  })();
