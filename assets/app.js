let lastShoutId = null;
const notify = (msg, type='info') => {
  const n = document.getElementById('notifications'); if (!n) return;
  const div = document.createElement('div');
  div.className = 'notification p-3 rounded-lg shadow text-white ' + (type==='success'?'bg-green-600': type==='error'?'bg-red-600':'bg-gray-800');
  div.textContent = msg; n.appendChild(div); setTimeout(()=>div.remove(), 3500);
};
async function fetchJSON(url, opts={}){
  const res = await fetch(url, Object.assign({ headers: {'X-CSRF': window.CSRF} }, opts));
  if (!res.ok) throw new Error('Request failed'); return await res.json();
}
async function loadShoutbox() {
  const box = document.getElementById('shoutbox'); if (!box) return;
  try {
    const data = await fetchJSON('api/shout_fetch.php' + (lastShoutId ? ('?after=' + encodeURIComponent(lastShoutId)) : ''));
    if (data.items && data.items.length) {
      data.items.forEach(it => {
        const div = document.createElement('div');
        div.className = 'flex items-start space-x-3 p-3 bg-white rounded-lg shadow-sm';
        div.innerHTML = `
          <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-lg">
            ${ (it.first_name||'U').charAt(0).toUpperCase() }
          </div>
          <div class="flex-1">
            <div class="flex items-center space-x-2 mb-1">
              <span class="font-semibold text-purple-600">${ it.first_name } ${ it.last_name } (@${ it.username })</span>
              <span class="text-xs text-gray-500">${ new Date(it.created_at).toLocaleString() }</span>
            </div>
            <p class="text-gray-800"></p>
          </div>`;
        div.querySelector('p').textContent = it.message;
        box.appendChild(div); box.scrollTop = box.scrollHeight; lastShoutId = it.id;
      });
    }
  } catch (e) { /* ignore */ }
}
async function sendShout() {
  const input = document.getElementById('shoutInput'); if (!input) return;
  const msg = input.value.trim(); if (!msg) return;
  try {
    const res = await fetchJSON('api/shout_post.php', {
      method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF': window.CSRF},
      body: JSON.stringify({message: msg})
    });
    if (res.ok) { input.value = ''; await loadShoutbox(); notify('Message sent!', 'success'); }
    else { notify(res.error || 'Please login to chat', 'error'); }
  } catch (e) { notify('Failed to send', 'error'); }
}
setInterval(loadShoutbox, 5000);
document.addEventListener('DOMContentLoaded', ()=>{ loadShoutbox(); });
