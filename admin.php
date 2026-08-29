<?php
declare(strict_types=1);
session_start();
if(($_SESSION['user']??'')!=='admin'){ http_response_code(403); exit('Administrator access required.'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin — Games Management</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#f5f7fb;color:#111;margin:0;padding:20px}
.container{max-width:1100px;margin:0 auto}
h1{font-size:1.6rem}
.table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #dfe3ea;border-radius:8px;overflow:hidden}
.table th{background:#eef2f7;padding:10px;font-weight:700;text-align:left}
.table td{padding:8px;border-top:1px solid #e7eaf0;vertical-align:middle}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}
.field{display:flex;flex-direction:column}
.field label{font-size:.8rem;color:#556}
input[type=text],textarea,select{padding:8px;border-radius:6px;border:1px solid #cbd2dc}
button{background:#2563eb;color:#fff;border:0;padding:8px 10px;border-radius:6px;cursor:pointer}
.small{background:#e5e7eb;color:#111;padding:6px 8px;border-radius:6px}
.actions{display:flex;gap:8px}
</style>
</head>
<body>
<div class="container">
<h1>Admin — Games Management</h1>
<p>Logged in as <strong>admin</strong>. Use this page to add/edit/delete games in Liste 1 / Liste 2. Changes are saved to community-data/community.json.</p>
<section style="margin-bottom:18px">
<h2>Create new game</h2>
<form id="add-form">
<div class="form-row">
<div class="field"><label>Game name</label><input type="text" name="name" required></div>
<div class="field"><label>List number (1 or 2)</label><select name="list"><option value="1">1</option><option value="2">2</option></select></div>
</div>
<div class="form-row">
<div class="field"><label>Players / notes</label><input type="text" name="players"></div>
<div class="field"><label>Genre</label><input type="text" name="genre"></div>
</div>
<div class="form-row">
<div class="field"><label>Steam URL</label><input type="text" name="steam"></div>
<div class="field"><label>Notes</label><input type="text" name="notes"></div>
</div>
<button type="submit">Create game</button>
</form>
</section>
<section>
<h2>Existing games</h2>
<table class="table" id="games-table">
<thead><tr><th>Id</th><th>Name</th><th>Players</th><th>Genre</th><th>Steam</th><th>Notes</th><th>List</th><th></th></tr></thead>
<tbody id="games-body"><tr><td colspan="8">Loading…</td></tr></tbody>
</table>
</section>

<!-- User management section -->
<section style="margin-top:18px">
  <h2>User management</h2>
  <p>Manage application users. Passwords are stored as hashes.</p>
  <div style="max-width:800px">
    <table class="table" id="users-table">
      <thead><tr><th>Username</th><th>Password (set new)</th><th></th></tr></thead>
      <tbody id="users-body"><tr><td colspan="3">Loading…</td></tr></tbody>
    </table>

    <h3 style="margin-top:12px">Create new user</h3>
    <form id="user-add-form" style="display:grid;grid-template-columns:1fr auto;gap:8px;max-width:380px">
      <input name="username" type="text" placeholder="username" required />
      <input name="password" type="password" placeholder="password" required />
      <div style="grid-column:1/-1"><button type="submit">Create user</button></div>
    </form>
  </div>
</section>

<hr />
<p style="margin-top:14px;font-size:.9rem;color:#667">After you add/edit games they will appear in the main page (Liste 1 or Liste 2). Make sure the webserver can write to <code>community-data/</code>.</p>
</div>
<script>
(async ()=>{
  const API='community-api.php';
  async function api(action, body){
    const url=new URL(API, location.href); url.searchParams.set('action', action);
    const init={credentials:'same-origin',headers:{'Accept':'application/json'}};
    if(body){ init.method='POST'; init.headers['Content-Type']='application/json'; init.body=JSON.stringify(body); }
    const res=await fetch(url, init); const text=await res.text();
    try{ const j=JSON.parse(text||'null'); if(!res.ok) throw new Error(j?.error||res.statusText); return j; }catch(e){ throw new Error('API error: '+(e.message||text)); }
  }
  function el(tag,attrs={},...children){ const n=document.createElement(tag); for(const k in attrs) if(k==='class') n.className=attrs[k]; else n.setAttribute(k, attrs[k]); for(const c of children) if(typeof c==='string') n.appendChild(document.createTextNode(c)); else if(c) n.appendChild(c); return n; }

  async function loadGames(){ const t=document.getElementById('games-body'); t.innerHTML='<tr><td colspan="8">Loading…</td></tr>'; try{ const r=await api('games'); const games=r.games||[]; const rows=[]; for(const g of games){ const tr=document.createElement('tr'); tr.dataset.id=String(g.id||''); tr.innerHTML=`<td class="small">${escapeHtml(String(g.id||''))}</td>`; tr.innerHTML+=`<td><input type="text" name="name" value="${escapeHtml(g.name||'')}"></td>`; tr.innerHTML+=`<td><input type="text" name="players" value="${escapeHtml(g.players||'')}"></td>`; tr.innerHTML+=`<td><input type="text" name="genre" value="${escapeHtml(g.genre||'')}"></td>`; tr.innerHTML+=`<td><input type="text" name="steam" value="${escapeHtml(g.steam||'')}"></td>`; tr.innerHTML+=`<td><input type="text" name="notes" value="${escapeHtml(g.notes||'')}"></td>`; tr.innerHTML+=`<td><select name="list"><option value="1" ${g.list==1?'selected':''}>1</option><option value="2" ${g.list==2?'selected':''}>2</option></select></td>`; tr.innerHTML+=`<td class="actions"><button data-save>Save</button><button data-delete style="background:#ef4444">Delete</button></td>`; rows.push(tr); }
      if(rows.length===0){ t.innerHTML='<tr><td colspan="8"><em>No games yet.</em></td></tr>'; } else { t.innerHTML=''; for(const r of rows) t.appendChild(r); }
  }catch(err){ t.innerHTML=`<tr><td colspan="8" style="color:#a00">${escapeHtml(err.message)}</td></tr>`; }
  }

  function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

  document.getElementById('add-form').addEventListener('submit', async e=>{
    e.preventDefault(); const f=new FormData(e.target); const body={name:f.get('name'),players:f.get('players'),genre:f.get('genre'),steam:f.get('steam'),notes:f.get('notes'),list:parseInt(f.get('list')||1,10)};
    try{ const res=await api('games_add', body); alert('Created: '+res.game.id); e.target.reset(); loadGames(); }catch(err){ alert(err.message); }
  });

  document.getElementById('games-body').addEventListener('click', async e=>{
    const tr=e.target.closest('tr'); if(!tr) return; const id=tr.dataset.id; if(e.target.matches('[data-save]')){
      const name=tr.querySelector('input[name="name"]').value; const players=tr.querySelector('input[name="players"]').value; const genre=tr.querySelector('input[name="genre"]').value; const steam=tr.querySelector('input[name="steam"]').value; const notes=tr.querySelector('input[name="notes"]').value; const list=parseInt(tr.querySelector('select[name="list"]').value||1,10);
      try{ await api('games_edit',{id,name,players,genre,steam,notes,list}); alert('Saved'); loadGames(); }catch(err){ alert(err.message); }
    }else if(e.target.matches('[data-delete]')){
      if(!confirm('Delete this game?')) return; try{ await api('games_delete',{id}); alert('Deleted'); loadGames(); }catch(err){ alert(err.message); }
    }
  });

  // --- Users management JS using existing API endpoints (users_list, users_add, users_edit, users_delete)
  async function loadUsers(){
    const t=document.getElementById('users-body'); t.innerHTML='<tr><td colspan="3">Loading…</td></tr>';
    try{
      const res = await api('users_list'); // returns { users: ["name", ...], admin: bool }
      const users = res.users || [];
      const rows = [];
      for(const u of users){
        const tr=document.createElement('tr');
        tr.innerHTML = `<td><strong>${escapeHtml(u)}</strong></td>` +
                       `<td><input type="password" name="password" placeholder="new password (leave blank to keep)"></td>` +
                       `<td class="actions"><button data-save data-user="${escapeHtml(u)}">Save</button><button data-delete data-user="${escapeHtml(u)}" style="background:#ef4444">Delete</button></td>`;
        rows.push(tr);
      }
      if(rows.length===0) t.innerHTML='<tr><td colspan="3"><em>No users.</em></td></tr>';
      else { t.innerHTML=''; for(const r of rows) t.appendChild(r); }
    }catch(err){ t.innerHTML=`<tr><td colspan="3" style="color:#a00">${escapeHtml(err.message)}</td></tr>`; }
  }

  document.getElementById('users-body').addEventListener('click', async e=>{
    const btn = e.target.closest('button'); if(!btn) return; const user = btn.dataset.user; if(btn.hasAttribute('data-save')){
      const tr = btn.closest('tr'); const pw = tr.querySelector('input[name="password"]').value;
      if(pw && pw.length < 4) return alert('Password must be at least 4 characters');
      try{
        // users_edit expects 'old' and optionally 'password'
        await api('users_edit', { old: user, password: (pw||'') });
        alert('Saved'); await loadUsers();
      }catch(err){ alert(err.message); }
    } else if(btn.hasAttribute('data-delete')){
      if(!confirm('Delete user '+user+'?')) return;
      try{ await api('users_delete', { username: user }); alert('Deleted'); await loadUsers(); }catch(err){ alert(err.message); }
    }
  });

  document.getElementById('user-add-form').addEventListener('submit', async e=>{
    e.preventDefault(); const f=new FormData(e.target); const u=f.get('username').trim(); const p=f.get('password')||''; if(!u||!p) return alert('username and password required');
    try{ await api('users_add', { username: u, password: p }); alert('Created'); e.target.reset(); await loadUsers(); }catch(err){ alert(err.message); }
  });

  // initial loads
  await loadGames();
  await loadUsers();
})();
</script>
</body>
</html>
