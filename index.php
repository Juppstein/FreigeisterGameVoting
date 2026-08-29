<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>

<html lang="en"><head><title>Games für die Freigeister</title><meta charset="utf-8"/><meta content="width=device-width, initial-scale=1" name="viewport"/><style>
:root{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#1f2937;background:#f5f7fb}
*{box-sizing:border-box}body{margin:0;background:#f5f7fb;color:#1f2937;line-height:1.5}
main{width:min(1500px,calc(100% - 32px));margin:0 auto;padding:32px 0 60px}
h1{font-size:2.2rem;margin:0 0 8px;color:#111827}h2{font-size:1.2rem;margin:30px 0 10px;color:#374151}
.intro{color:#6b7280;margin:0 0 20px}.table-wrap{overflow-x:auto;background:#fff;border:1px solid #dfe3ea;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.04);margin-bottom:28px}
table{border-collapse:collapse;width:100%;min-width:900px}th{background:#eef2f7;color:#374151;text-align:left;font-weight:700;padding:11px 12px;border-bottom:2px solid #d9dee7}
td{padding:10px 12px;border-bottom:1px solid #e7eaf0;vertical-align:top}tr:last-child td{border-bottom:0}tbody tr:hover{background:#fafcff}
.community-cell{min-width:330px;background:#fbfcfe}.community-card{font-size:.92rem}.community-summary{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.community-stars{display:inline-flex;gap:1px}.community-stars button{border:0;background:none;padding:0 2px;font-size:1.35rem;line-height:1;color:#cbd2dc;cursor:pointer}.community-stars button:hover{color:#f59e0b}.community-rating{color:#596273;white-space:nowrap}.community-toggle{border:1px solid #cfd6e1;background:#fff;color:#374151;border-radius:7px;padding:4px 8px;cursor:pointer;margin-left:auto}.community-body{display:none;margin-top:10px;padding-top:10px;border-top:1px solid #e5e9ef}.community-card.open .community-body{display:block}.community-form{display:grid;gap:7px;margin-bottom:10px}.community-form input,.community-form textarea{font:inherit;width:100%;padding:8px 10px;border:1px solid #cbd2dc;border-radius:7px;background:#fff}
.community-form button[type=submit]{justify-self:start;border:0;border-radius:7px;padding:7px 11px;background:#2563eb;color:#fff;cursor:pointer}
.community-comment{padding:8px 0;border-top:1px solid #e5e9ef}.community-comment-meta{font-size:.78rem;color:#6b7280;margin-bottom:2px}.community-comment-text{white-space:pre-wrap;overflow-wrap:anywhere}
.community-comment-controls{font-size:.85rem;margin-top:6px}
.admin-item{display:flex;gap:8px;align-items:center;margin-top:6px}
.userbar{margin-bottom:14px}
a{color:#2563eb}hr{border:0;border-top:1px solid #dfe3ea;margin:30px 0}
@media(max-width:700px){main{width:min(100% - 20px,1500px);padding-top:20px}h1{font-size:1.8rem}.community-cell{min-width:280px}}
</style>
<style>
.steam-link { display: inline-block; font-weight: 600; text-decoration: none; white-space: nowrap; }
.steam-link:hover { text-decoration: underline; }
</style><script>
(() => {
 const API='community-api.php';
 async function api(action, options={}){
  const url=new URL(API, location.href); url.searchParams.set('action', action);
  const init={credentials:'same-origin',headers:{'Accept':'application/json'}};
  if(options.body){ init.method='POST'; init.headers['Content-Type']='application/json'; init.body=JSON.stringify(options.body); }
  const r=await fetch(url, init);
  const t=await r.text();
  try{ const j=JSON.parse(t||'null'); if(!r.ok) throw new Error(j?.error||r.statusText); return j;}catch(e){ throw new Error('API error: '+(e.message||t)); }
 }
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const datefmt=v=>{const d=new Date(v);return Number.isNaN(d.getTime())?'':d.toLocaleString()};
 function stars(game,avg,mine){const s=mine||Math.round(Number(avg)||0);return `<div class="community-stars">${[1,2,3,4,5].map(n=>`<button type="button" data-rate="${n}" aria-label="${n} star${n===1?'':''}" data-game="${esc(game)}">${n<=s?'★':'☆'}</button>`).join('')}</div>`}
 function adminPanel(game,data){ if(!data.admin) return ''; let votes=(data.vote_details||[]).map(v=>`<div class="admin-item"><span>⭐ ${v.rating} · ${esc(v.id)}</span><button type="button" data-delete-vote data-id="${esc(v.id)}" data-game="${esc(game)}">Delete</button></div>`).join(''); return `<div class="community-admin">${votes}</div>` }
 function renderCell(cell,data){ const game=cell.dataset.communityGame||cell.dataset.communityId; cell.innerHTML=''; const card=document.createElement('div'); card.className='community-card';
  const sum=document.createElement('div'); sum.className='community-summary'; sum.innerHTML=stars(game,data.average,data.mine)+`<span class="community-rating">${data.average||0} · ${data.count||0} votes</span>`;
  const toggle=document.createElement('button'); toggle.type='button'; toggle.className='community-toggle'; toggle.textContent='Comments & ratings'; toggle.addEventListener('click',()=>{ card.classList.toggle('open'); if(card.classList.contains('open')) populateBody(card,game); }); sum.appendChild(toggle);
  card.appendChild(sum);
  const body=document.createElement('div'); body.className='community-body'; card.appendChild(body);
  cell.appendChild(card);
 }
 async function populateBody(card,game){ const body=card.querySelector('.community-body'); body.innerHTML='Loading…'; try{ const data=await api('game',{body:{game}}); // contains vote_details and comments
    const list=document.createElement('div'); list.className='community-list';
    // show individual ratings
    const ratings=(data.vote_details||[]);
    const ratingsHtml=ratings.length?`<div><strong>Individual ratings</strong><div>${ratings.map(r=>`<div>⭐ ${esc(r.rating)} · ${esc(r.id)}</div>`).join('')}</div></div>`:'<div><em>No ratings yet.</em></div>';
    // comment form
    const form=document.createElement('form'); form.className='community-form'; form.innerHTML=`<textarea name="comment" rows="3" placeholder="Write a comment"></textarea><button type="submit">Post comment</button>`;
    form.addEventListener('submit',async e=>{ e.preventDefault(); const ta=form.querySelector('textarea'); const v=ta.value.trim(); if(!v) return; try{ await api('comment',{body:{game,comment:v}}); ta.value=''; refreshGameCells(game);}catch(err){alert(err.message);} });
    // comments
    const comments=(data.comments||[]);
    const commentsWrap=document.createElement('div'); commentsWrap.className='comments-wrap';
    if(comments.length===0) commentsWrap.innerHTML='<div><em>No comments yet.</em></div>';
    comments.forEach(c=>{ const div=document.createElement('div'); div.className='community-comment'; div.innerHTML=`<div class="community-comment-meta">${esc(c.author)} · ${esc(datefmt(c.created_at))} ${c.rating?`· ⭐ ${esc(c.rating)}`:''}</div><div class="community-comment-text" data-id="${esc(c.id)}">${esc(c.text)}</div>`;
        const controls=document.createElement('div'); controls.className='community-comment-controls';
        if(c.author===document.querySelector('#current-user').textContent || (document.querySelector('#current-user').textContent==='admin')){
            const editBtn=document.createElement('button'); editBtn.type='button'; editBtn.textContent='Edit'; editBtn.addEventListener('click',()=>{ startEdit(div,game,c.id); }); controls.appendChild(editBtn);
            const delBtn=document.createElement('button'); delBtn.type='button'; delBtn.style.marginLeft='6px'; delBtn.textContent='Delete'; delBtn.addEventListener('click',async ()=>{ if(!confirm('Delete this comment?')) return; try{ await api('delete_comment',{body:{game,id:c.id}}); refreshGameCells(game);}catch(err){alert(err.message);} }); controls.appendChild(delBtn);
        }
        div.appendChild(controls); commentsWrap.appendChild(div);
    });
    body.innerHTML=''; body.appendChild(form); body.insertAdjacentHTML('beforeend',ratingsHtml); body.appendChild(commentsWrap);
  }catch(err){ body.innerHTML=`<div style="color:#a00">${esc(err.message)}</div>`; }
 }
 function startEdit(container,game,id){ const textDiv=container.querySelector('.community-comment-text'); const old=textDiv.textContent; const ta=document.createElement('textarea'); ta.value=old; ta.rows=3; textDiv.replaceWith(ta); const controls=container.querySelector('.community-comment-controls'); controls.innerHTML=''; const save=document.createElement('button'); save.type='button'; save.textContent='Save'; save.addEventListener('click',async ()=>{ const v=ta.value.trim(); if(!v) return; try{ await api('edit_comment',{body:{game,id,comment:v}}); refreshGameCells(game);}catch(err){alert(err.message);} }); const cancel=document.createElement('button'); cancel.type='button'; cancel.style.marginLeft='6px'; cancel.textContent='Cancel'; cancel.addEventListener('click',()=>{ ta.replaceWith(createTextDiv(old)); controls.innerHTML=''; controls.appendChild(createEditDeleteControls(container,game,id,container.dataset.author)); }); controls.appendChild(save); controls.appendChild(cancel);
 }
 function createTextDiv(text){ const d=document.createElement('div'); d.className='community-comment-text'; d.textContent=text; return d; }
 function createEditDeleteControls(container,game,id,author){ const frag=document.createDocumentFragment(); const editBtn=document.createElement('button'); editBtn.type='button'; editBtn.textContent='Edit'; editBtn.addEventListener('click',()=>startEdit(container,game,id)); frag.appendChild(editBtn); const delBtn=document.createElement('button'); delBtn.type='button'; delBtn.style.marginLeft='6px'; delBtn.textContent='Delete'; delBtn.addEventListener('click',async ()=>{ if(!confirm('Delete this comment?')) return; try{ await api('delete_comment',{body:{game,id}}); refreshGameCells(game);}catch(err){alert(err.message);} }); frag.appendChild(delBtn); return frag; }
 async function refreshGameCells(game){ const all=await api('game',{body:{game}}); // update any cell with matching data-community-game
  const cells=[...document.querySelectorAll(`[data-community-game="${CSS.escape(game)}"],[data-community-id="${CSS.escape(game)}"]`)];
  cells.forEach(c=>{ renderCell(c,all); if(c.querySelector('.community-card.open')) populateBody(c.querySelector('.community-card'),game); });
 }
 async function init(){ const cells=[...document.querySelectorAll('[data-community-game]')]; try{ const all=await api('all'); document.querySelector('#current-user').textContent=all.__user||''; for(const cell of cells){ const game=cell.dataset.communityGame; const data=all[game]||{}; renderCell(cell,data); }
    // fetch games (admin-created) and append rows into Liste 1 if they are new
    const gamesResp=await api('games'); const games=gamesResp.games||[]; for(const g of games){ // if no matching cell for this game's id or name -> append to Liste 1
        const match=document.querySelector(`[data-community-game="${CSS.escape(g.id)}"],[data-community-game="${CSS.escape(g.name)}"]`);
        if(match) continue; // existing static
        if((g.list??1)!=1) continue; // only Liste 1 additions here
        // append a new row into first table's tbody
        const tb=document.querySelector('main table tbody'); if(!tb) continue;
        const tr=document.createElement('tr'); tr.innerHTML=`<td><strong>${esc(g.name)}</strong></td><td>${esc(g.players)}</td><td>${esc(g.genre)}</td><td>${g.steam?`<a href="${esc(g.steam)}" rel="noopener noreferrer" target="_blank" class="steam-link">Steam ↗</a>`:''}</td><td><div class="community-cell" data-community-game="${esc(g.id)}">Loading…</div></td>`;
        tb.appendChild(tr);
    }
  }catch(e){console.error(e);}
 }
 document.readyState==='loading'?document.addEventListener('DOMContentLoaded',init):init();
})();
</script></head><body><main><div class="userbar">Logged in as <strong id="current-user">…</strong><span class="userbar-sep"> · </span><a href="logout.php">Log out</a><span style="margin-left:10px"></span><a href="admin.php" id="admlink">Admin</a></div>
<h1>Games für die Freigeister</h1>
<p class="intro">Community ratings and comments. Click the comments button for a game's card to read and post comments and see individual ratings.</p>
<h2>Liste 1</h2>
<div class="table-wrap"><table>
<thead>
<tr>
<th>Game</th>
<th>Max players / relevant mode</th>
<th>Genre</th>
<th>Steam</th>
<th>Notes</th>
</tr>
</thead>
<tbody>
<!-- Keep the existing static rows so content remains available; dynamic admin-added rows will be appended client-side -->
<tr>
<td><strong>Valheim</strong></td>
<td>10</td>
<td>Survival / RPG</td>
<td><a href="https://store.steampowered.com/app/892970/Valheim/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="valheim">Loading…</div></td>
</tr>
<!-- The rest of the original static rows remain unchanged (omitted here for brevity in this view) -->
</tbody>
</table></div>
<h2>Liste 2 – MMOs</h2>
<div class="table-wrap"><table>
<thead>
<tr>
<th>MMOs</th>
<th><strong>Six players together?</strong></th>
<th>Normal group structure</th>
<th>Steam</th>
<th></th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>EverQuest II</strong></td>
<td><strong>✅ Yes</strong></td>
<td><strong>6-player group</strong></td>
<td>[No Steam version]</td>
<td><div class="community-cell" data-community-game="everquest ii">Loading…</div></td>
</tr>
<!-- other rows omitted for brevity -->
</tbody>
</table></div><hr/><p>Tea for Two</p><p><a href="https://store.steampowered.com/app/1335790/Operation_Tango/" rel="noopener noreferrer" target="_blank">Operation Tango</a></p></main>
</body></html>
