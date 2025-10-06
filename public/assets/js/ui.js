(function(){
  const btn = document.getElementById('hamburger');
  const menu = document.getElementById('mobileMenu');
  if(btn && menu){
    btn.addEventListener('click', () => {
      const hidden = menu.classList.contains('hidden');
      menu.classList.toggle('hidden');
      btn.setAttribute('aria-expanded', hidden ? 'true' : 'false');
    });
    menu.querySelectorAll('a').forEach(a => a.addEventListener('click', ()=>{
      if(!menu.classList.contains('hidden')){
        menu.classList.add('hidden');
        btn.setAttribute('aria-expanded','false');
      }
    }));
  }
  // Active link highlight
  const nav = document.getElementById('mainNav');
  const current = window.location.pathname.replace(/\/$/,'');
  if(nav){
    nav.querySelectorAll('a[data-path]').forEach(link=>{
      const lp = link.getAttribute('data-path');
      if(lp === current || (lp === '/' && current === '')){
        link.classList.add('text-primary','font-semibold','relative');
        link.classList.add('after:absolute','after:-bottom-2','after:left-0','after:w-full','after:h-1','after:bg-primary','after:rounded-full');
        link.setAttribute('aria-current','page');
      } else {
        link.removeAttribute('aria-current');
      }
    });
  }
  // Scroll header shadow
  const header = document.getElementById('siteHeader');
  const onScroll = () => {
    if(window.scrollY > 12){
      header && header.classList.add('shadow-lg');
    } else {
      header && header.classList.remove('shadow-lg');
    }
  };
  window.addEventListener('scroll', onScroll, {passive:true});
  onScroll();
})();
