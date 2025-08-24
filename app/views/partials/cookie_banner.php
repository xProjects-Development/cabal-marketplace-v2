<?php
// app/views/partials/cookie_banner.php
require_once __DIR__ . '/../../cookie_consent.php';
?>
<style>
.ccy-wrap{position:fixed;left:0;right:0;bottom:0;z-index:9999;display:none}
.ccy-card{margin:16px auto;max-width:920px;background:#111827;color:#f9fafb;border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:18px}
.ccy-actions{display:flex;gap:8px;flex-wrap:wrap}
.ccy-btn{border:0;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer}
.ccy-accept{background:#16a34a;color:#fff}
.ccy-reject{background:#374151;color:#fff}
.ccy-customize{background:#f59e0b;color:#111827}
@media (max-width:640px){.ccy-card{margin:0;border-radius:0}}
</style>
<div id="ccy" class="ccy-wrap" aria-live="polite">
  <div class="ccy-card">
    <div style="display:flex;gap:14px;align-items:flex-start;justify-content:space-between">
      <div style="flex:1">
        <div style="font-size:16px;font-weight:800;margin-bottom:6px">We use cookies</div>
        <div style="font-size:14px;opacity:.9">
          We use necessary cookies to make the site work. With your consent, we’ll also use analytics and marketing cookies to improve your experience.
          See our <a href="/cookies.php" style="color:#93c5fd;text-decoration:underline">Cookie Policy</a>.
        </div>
      </div>
      <div class="ccy-actions">
        <button class="ccy-btn ccy-reject" id="ccyReject">Reject all</button>
        <button class="ccy-btn ccy-customize" id="ccyCustomize">Customize</button>
        <button class="ccy-btn ccy-accept" id="ccyAccept">Accept all</button>
      </div>
    </div>
  </div>
</div>

<!-- Customize modal -->
<div id="ccyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center">
  <div style="width:min(720px,92vw);background:#fff;border-radius:14px;padding:18px">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <h3 style="font-size:18px;font-weight:800">Cookie preferences</h3>
      <button id="ccyClose" style="font-size:20px;border:0;background:transparent;cursor:pointer">×</button>
    </div>
    <div style="font-size:14px;color:#111827;margin:10px 0 6px">Choose which cookies to allow. Necessary cookies are always on.</div>
    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin:8px 0">
      <label style="display:flex;align-items:center;gap:10px">
        <input type="checkbox" checked disabled>
        <div><div style="font-weight:700">Necessary</div><div style="font-size:13px;color:#6b7280">Required for core site functionality.</div></div>
      </label>
    </div>
    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin:8px 0">
      <label style="display:flex;align-items:center;gap:10px">
        <input type="checkbox" id="ccyAnalytics">
        <div><div style="font-weight:700">Analytics</div><div style="font-size:13px;color:#6b7280">Helps us understand site usage.</div></div>
      </label>
    </div>
    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin:8px 0">
      <label style="display:flex;align-items:center;gap:10px">
        <input type="checkbox" id="ccyMarketing">
        <div><div style="font-weight:700">Marketing</div><div style="font-size:13px;color:#6b7280">Personalized content or ads.</div></div>
      </label>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px">
      <button class="ccy-btn ccy-reject" id="ccySaveReject">Reject all</button>
      <button class="ccy-btn ccy-accept" id="ccySavePrefs">Save preferences</button>
    </div>
  </div>
</div>

<script>
(function(){
  function getCookie(name){
    var found = (document.cookie.split('; ').find(function(row){ return row.indexOf(name+'=')===0; })||'').split('=')[1]||'';
    return found;
  }
  function setCookie(name, value, days){
    var d = new Date(); d.setTime(d.getTime() + (days*24*60*60*1000));
    document.cookie = name + '=' + value + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
  }
  function showBannerIfNeeded(){
    try{
      var raw = decodeURIComponent(getCookie('cookie_prefs')||'{}') || '{}';
      var prefs = JSON.parse(raw);
      if (!prefs || Object.keys(prefs).length===0) document.getElementById('ccy').style.display='block';
    }catch(e){ document.getElementById('ccy').style.display='block'; }
  }
  function openModal(state){
    document.getElementById('ccyModal').style.display = state ? 'flex' : 'none';
  }
  var accept = document.getElementById('ccyAccept');
  var reject = document.getElementById('ccyReject');
  var customize = document.getElementById('ccyCustomize');
  var savePrefs = document.getElementById('ccySavePrefs');
  var saveReject = document.getElementById('ccySaveReject');
  var closeBtn = document.getElementById('ccyClose');
  var analytics = document.getElementById('ccyAnalytics');
  var marketing = document.getElementById('ccyMarketing');
  if (accept) accept.addEventListener('click', function(){
    setCookie('cookie_prefs', encodeURIComponent(JSON.stringify({analytics:true, marketing:true})), 365);
    document.getElementById('ccy').style.display='none'; location.reload();
  });
  if (reject) reject.addEventListener('click', function(){
    setCookie('cookie_prefs', encodeURIComponent(JSON.stringify({analytics:false, marketing:false})), 365);
    document.getElementById('ccy').style.display='none'; location.reload();
  });
  if (customize) customize.addEventListener('click', function(){ openModal(true); });
  if (closeBtn) closeBtn.addEventListener('click', function(){ openModal(false); });
  if (savePrefs) savePrefs.addEventListener('click', function(){
    setCookie('cookie_prefs', encodeURIComponent(JSON.stringify({analytics: !!analytics.checked, marketing: !!marketing.checked})), 365);
    document.getElementById('ccy').style.display='none'; openModal(false); location.reload();
  });
  if (saveReject) saveReject.addEventListener('click', function(){
    setCookie('cookie_prefs', encodeURIComponent(JSON.stringify({analytics:false, marketing:false})), 365);
    document.getElementById('ccy').style.display='none'; openModal(false); location.reload();
  });
  try {
    var raw = decodeURIComponent(getCookie('cookie_prefs')||'{}') || '{}';
    var prefs = JSON.parse(raw);
    if (typeof prefs.analytics === 'boolean') analytics.checked = prefs.analytics;
    if (typeof prefs.marketing === 'boolean') marketing.checked = prefs.marketing;
  } catch(e){}
  showBannerIfNeeded();
})();
</script>
<?php cookie_consent_js_guard(); ?>
