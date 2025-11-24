<?php
require_once __DIR__ . '/../includes/init.php';
// simple public registration page
ob_start();
?>
<h1 class="text-2xl font-semibold mb-4" style="color:#025529">Register</h1>
<div class="bg-white p-4 rounded shadow max-w-md">
  <form id="registerForm" class="grid gap-3">
    <label>Username
      <input name="username" required class="w-full p-2 border rounded" />
    </label>
    <label>Email
      <input name="email" type="email" required class="w-full p-2 border rounded" />
    </label>
    <label>Password
      <input name="password" type="password" required class="w-full p-2 border rounded" />
    </label>
    <div>
      <button id="regBtn" class="px-3 py-2 rounded btn-brand text-white">Register</button>
    </div>
    <div id="regMsg" class="mt-2"></div>
  </form>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const btn = document.getElementById('regBtn');
  const msg = document.getElementById('regMsg');
  btn.disabled = true; msg.innerText = '';
  const f = new FormData(e.target);
  const body = Object.fromEntries(f.entries());
  try {
    const res = await fetch('<?= dirname(baseUrl()) ?>/api/register.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
    const j = await res.json();
    if (j.success) {
      msg.className = 'text-green-600';
      msg.innerText = j.message || 'Registered. Please check your email to verify your account.';
    } else {
      msg.className = 'text-red-600'; msg.innerText = j.error || JSON.stringify(j);
    }
  } catch (err) {
    msg.className = 'text-red-600'; msg.innerText = 'Request failed';
  } finally { btn.disabled = false; }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
