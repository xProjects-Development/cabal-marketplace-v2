<?php require_once __DIR__ . '/app/bootstrap.php'; include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-3xl mx-auto px-4 py-12">
  <h1 class="text-3xl font-extrabold mb-3">Cookie Policy</h1>
  <p class="text-gray-600 mb-6">Last updated: August 22, 2025</p>

  <div class="bg-white rounded-2xl shadow p-6 space-y-4">
    <p>We use cookies and similar technologies to run this website and to help us understand how you use it.</p>

    <h2 class="text-xl font-bold mt-4">Types of cookies</h2>
    <ul class="list-disc pl-6 space-y-1 text-gray-800">
      <li><strong>Necessary</strong>: required for core functionality (e.g., login, CSRF protection, preferences).</li>
      <li><strong>Analytics</strong>: helps us measure and improve performance.</li>
      <li><strong>Marketing</strong>: used to personalize content or measure campaigns.</li>
    </ul>

    <h2 class="text-xl font-bold mt-4">Your choices</h2>
    <p>You can accept, reject, or customize non-essential cookies using the banner, or change your decision below.</p>

    <form id="ccyForm" class="space-y-3">
      <label class="flex items-center gap-2"><input type="checkbox" checked disabled> <span>Necessary (always on)</span></label>
      <label class="flex items-center gap-2"><input type="checkbox" id="ccyA"> <span>Analytics</span></label>
      <label class="flex items-center gap-2"><input type="checkbox" id="ccyM"> <span>Marketing</span></label>
      <div class="pt-2">
        <button type="button" id="ccySave" class="bg-purple-600 text-white px-5 py-2 rounded-lg">Save preferences</button>
        <button type="button" id="ccyReject" class="ml-2 bg-gray-700 text-white px-5 py-2 rounded-lg">Reject all</button>
      </div>
    </form>

    <h2 class="text-xl font-bold mt-4">Managing cookies in your browser</h2>
    <p>You can also clear or block cookies at the browser level. Instructions are available in your browser’s help pages.</p>

    <h2 class="text-xl font-bold mt-4">Contact</h2>
    <p>If you have questions about this policy, contact us via the site administrator by letters@comarketplace.eu.</p>
  </div>
</section>
<script>
(function(){
  function getCookie(name){
    var found = (document.cookie.split('; ').find(function(r){ return r.indexOf(name+'=')===0; })||'').split('=')[1]||'';
    return found;
  }
  function setCookie(name,value,days){var d=new Date();d.setTime(d.getTime()+(days*24*60*60*1000));document.cookie=name+'='+value+'; expires='+d.toUTCString()+'; path=/; SameSite=Lax';}
  var A=document.getElementById('ccyA'), M=document.getElementById('ccyM');
  try {
    var raw = decodeURIComponent(getCookie('cookie_prefs')||'{}') || '{}';
    var prefs = JSON.parse(raw);
    if (typeof prefs.analytics==='boolean') A.checked=prefs.analytics;
    if (typeof prefs.marketing==='boolean') M.checked=prefs.marketing;
  } catch(e){}
  document.getElementById('ccySave').addEventListener('click', function(){ setCookie('cookie_prefs', encodeURIComponent(JSON.stringify({analytics:!!A.checked, marketing:!!M.checked})), 365); alert('Saved.'); location.reload(); });
  document.getElementById('ccyReject').addEventListener('click', function(){ setCookie('cookie_prefs', encodeURIComponent(JSON.stringify({analytics:false, marketing:false})), 365); alert('Saved.'); location.reload(); });
})();
</script>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
