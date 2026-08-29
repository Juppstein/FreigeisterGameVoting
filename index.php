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
.community-stars{display:inline-flex;gap:1px}.community-stars button{border:0;background:none;padding:0 2px;font-size:1.35rem;line-height:1;color:#cbd2dc;cursor:pointer}.community-stars button:hover{color:#111}.community-stars button.selected{color:#f59e0b}
.community-rating{color:#596273;white-space:nowrap}.community-toggle{border:1px solid #cfd6e1;background:#fff;color:#374151;border-radius:7px;padding:4px 8px;cursor:pointer;margin-left:auto}.community-body{display:none;margin-top:10px;padding-top:10px;border-top:1px solid #e5e9ef}.community-card.open .community-body{display:block}
.community-form{display:grid;gap:7px;margin-bottom:10px}.community-form input,.community-form textarea{font:inherit;width:100%;padding:8px 10px;border:1px solid #cbd2dc;border-radius:7px;background:#fff}
.community-form button[type=submit]{justify-self:start;border:0;border-radius:7px;padding:7px 11px;background:#2563eb;color:#fff;cursor:pointer}.community-form button[type=submit]:hover{background:#1e40af}
.community-comment{padding:8px 0;border-top:1px solid #e5eef}.community-comment-meta{font-size:.78rem;color:#6b7280;margin-bottom:2px}.community-comment-text{white-space:pre-wrap;overflow-wrap:anywhere}
.community-comment-controls{margin-top:6px}
.community-empty{color:#6b7280;padding:6px 0}
.admin-item{display:flex;align-items:center;gap:8px}
a{color:#2563eb}hr{border:0;border-top:1px solid #dfe3ea;margin:30px 0}
@media(max-width:700px){main{width:min(100% - 20px,1500px);padding-top:20px}h1{font-size:1.8rem}.community-cell{min-width:280px}}
</style>
<style>
.steam-link {
  display: inline-block;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}
.steam-link:hover {
  text-decoration: underline;
}
</style><script>
(() => {
 const API='community-api.php';
 async function api(action, options={}){const url=new URL(API, location.href);url.searchParams.set('action', action);const init={credentials:'same-origin',headers:{'Accept':'application/json'}};if(options.body){init.method='POST';init.headers['Content-Type']='application/json';init.body=JSON.stringify(options.body);}const r=await fetch(url,init);const t=await r.text();let d;try{d=JSON.parse(t)}catch(e){throw new Error(`Server did not return JSON (HTTP ${r.status}).`)}if(!r.ok||d.error)throw new Error(d.error||`HTTP ${r.status}`);return d}
 const esc=v=>String(v??'').replace(/[&<>&"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const date=v=>{const d=new Date(v);return Number.isNaN(d.getTime())?'':d.toLocaleString()};
 function slugifyClient(s){ if(!s) return ''; s = String(s).toLowerCase(); s = s.replace(/[^a-z0-9]+/g,'-'); s = s.replace(/^-+|-+$/g,''); return s.slice(0,60); }
 function stars(game,avg,mine){const s=mine||Math.round(Number(avg)||0);return `<div class="community-stars">${[1,2,3,4,5].map(n=>`<button type="button" data-rate="${n}" aria-label="${n} star${n===1?'':'s'}" class="${s>=n?'selected':''}">★</button>`).join('')}</div>`}
 function adminPanel(game,data){if(!data.admin)return '';let votes=(data.vote_details||[]).map(v=>`<div class="admin-item"><span>⭐ ${v.rating} · ${esc(v.id)}</span><button type="button" data-delete-vote="${esc(v.id)}">Delete vote</button></div>`).join('');return `<div class="community-admin"><strong>Admin</strong>${votes||'<div class="community-empty">No votes.</div>'}</div>`}
 function renderCell(cell,data){ const gameKey=cell.dataset.communityGame||''; cell.innerHTML=''; const card=document.createElement('div'); card.className='community-card'; const summary=document.createElement('div'); summary.className='community-summary'; summary.innerHTML=stars(gameKey,data.average,data.mine)+`<span class="community-rating">${Number(data.average||0).toFixed(1)}/5 · ${data.count||0} vote${(data.count||0)===1?'':'s'}</span>`; const toggle=document.createElement('button'); toggle.type='button'; toggle.className='community-toggle'; toggle.textContent=`Comments (${(data.comments||[]).length})`; toggle.addEventListener('click',()=>card.classList.toggle('open')); summary.appendChild(toggle); card.appendChild(summary); const body=document.createElement('div'); body.className='community-body'; card.appendChild(body); cell.appendChild(card);
  // attach star handlers for voting
  setupStarHandlers(summary, gameKey, data, card);
}

async function setupStarHandlers(summaryElement, gameKey, initialData, card){
  const starBtns = summaryElement.querySelectorAll('.community-stars button');
  if(!starBtns) return;
  for(const b of starBtns){
    b.addEventListener('click', async (e)=>{
      try{
        const n = parseInt(b.getAttribute('data-rate'),10)||0;
        await api('vote',{body:{game:gameKey,rating:n}});
        // refresh game data and update UI
        const refreshed = await api('game',{body:{game:gameKey}});
        // update summary stars and rating text
        const ratingSpan = summaryElement.querySelector('.community-rating');
        summaryElement.querySelector('.community-stars').innerHTML = ([1,2,3,4,5].map(x=>`<button type="button" data-rate="${x}" aria-label="${x} star${x===1?'':'s'}" class="${(refreshed.mine||Math.round(refreshed.average||0))>=x?'selected':''}">★</button>`).join(''));
        if(ratingSpan) ratingSpan.textContent = `${Number(refreshed.average||0).toFixed(1)}/5 · ${refreshed.count||0} vote${(refreshed.count||0)===1?'':'s'}`;
        // reattach handlers to new buttons
        setupStarHandlers(summaryElement, gameKey, refreshed, card);
        // if comments panel is open, refresh its contents
        if(card.classList.contains('open')){
          const body = card.querySelector('.community-body'); if(body) {
            body.innerHTML = '';
            const form=document.createElement('form'); form.className='community-form'; form.innerHTML=`<textarea name="comment" rows="3" placeholder="Write a comment"></textarea><button type="submit">Post comment</button>`;
            form.addEventListener('submit', async e=>{ e.preventDefault(); const ta=form.querySelector('textarea'); const v=ta.value.trim(); if(!v) return; try{ await api('comment',{body:{game:gameKey,comment:v}}); ta.value=''; const refreshed2=await api('game',{body:{game:gameKey}}); renderCommentSection(body,refreshed2,gameKey); }catch(err){ alert(err.message); } });
            body.appendChild(form);
            const refreshed2=await api('game',{body:{game:gameKey}});
            renderCommentSection(body, refreshed2, gameKey);
          }
        }
      }catch(err){ alert(err.message); }
    });
  }
}

async function populateBody(card, gameId){ const body=card.querySelector('.community-body'); body.innerHTML='Loading…'; try{ const data=await api('game',{body:{game:gameId}}); body.innerHTML=''; const form=document.createElement('form'); form.className='community-form'; form.innerHTML=`<textarea name="comment" rows="3" placeholder="Write a comment"></textarea><button type="submit">Post comment</button>`; form.addEventListener('submit', async e=>{ e.preventDefault(); const ta=form.querySelector('textarea'); const v=ta.value.trim(); if(!v) return; try{ await api('comment',{body:{game:gameId,comment:v}}); ta.value=''; const refreshed=await api('game',{body:{game:gameId}}); renderCommentSection(body,refreshed,gameId); }catch(err){ alert(err.message); } }); body.appendChild(form); const refreshed=await api('game',{body:{game:gameId}}); renderCommentSection(body, refreshed, gameId); }catch(err){ body.innerHTML=`<div style="color:#a00">${esc(err.message)}</div>`; } }

function renderCommentSection(body,data,gameId){
  const ratings=(data.vote_details||[]);
  const ratingsHtml=document.createElement('div');
  ratingsHtml.className='ratings-list';
  ratingsHtml.innerHTML=ratings.length?`<div><strong>Individual ratings</strong><div>${ratings.map(r=>`<div>⭐ ${esc(r.rating)} · ${esc(r.id)}</div>`).join('')}</div></div>`:'<div class="community-empty">No ratings yet.</div>';
  const existingRatings=body.querySelector('.ratings-list'); if(existingRatings) existingRatings.replaceWith(ratingsHtml); else body.appendChild(ratingsHtml);

  const commentsWrap=document.createElement('div'); commentsWrap.className='comments-wrap';
  if((data.comments||[]).length===0) commentsWrap.innerHTML='<div class="community-empty">No comments yet.</div>';
  (data.comments||[]).forEach(c=>{
    const div=document.createElement('div'); div.className='community-comment';
    div.innerHTML=`<div class="community-comment-meta">${esc(c.author)} · ${esc(date(c.created_at))} ${c.rating?`· ⭐ ${esc(c.rating)}`:''}</div>`;
    const textDiv=document.createElement('div'); textDiv.className='community-comment-text'; textDiv.dataset.id = c.id||''; textDiv.textContent = c.text||'';
    div.appendChild(textDiv);

    const controls=document.createElement('div'); controls.className='community-comment-controls';
    const currentUser = document.querySelector('#current-user').textContent;
    if(c.author===currentUser || currentUser==='admin'){
      const delBtn=document.createElement('button'); delBtn.type='button'; delBtn.style.marginLeft='6px'; delBtn.textContent='Delete'; delBtn.addEventListener('click',async ()=>{ if(!confirm('Delete this comment?')) return; try{ await api('delete_comment',{body:{game:gameId,id:c.id}}); const refreshed=await api('game',{body:{game:gameId}}); renderCommentSection(body,refreshed,gameId); }catch(err){alert(err.message);} });
      controls.appendChild(delBtn);
    }
    // Edit button for own comments
    if(c.author===currentUser){
      const editBtn=document.createElement('button'); editBtn.type='button'; editBtn.style.marginLeft='6px'; editBtn.textContent='Edit';
      editBtn.addEventListener('click',()=>{
        // replace textDiv with an edit form
        const ta=document.createElement('textarea'); ta.rows=3; ta.value = c.text||''; ta.style.width='100%';
        const saveBtn=document.createElement('button'); saveBtn.type='button'; saveBtn.textContent='Save'; saveBtn.style.marginLeft='6px';
        const cancelBtn=document.createElement('button'); cancelBtn.type='button'; cancelBtn.textContent='Cancel'; cancelBtn.style.marginLeft='6px';
        const editContainer=document.createElement('div'); editContainer.appendChild(ta); editContainer.appendChild(saveBtn); editContainer.appendChild(cancelBtn);
        div.replaceChild(editContainer, textDiv);
        saveBtn.addEventListener('click', async ()=>{
          const newText = ta.value.trim(); if(newText==='') return alert('Comment cannot be empty');
          try{ await api('edit_comment',{body:{game:gameId,id:c.id,comment:newText}}); const refreshed=await api('game',{body:{game:gameId}}); renderCommentSection(body,refreshed,gameId); }catch(err){ alert(err.message); }
        });
        cancelBtn.addEventListener('click', ()=>{ renderCommentSection(body,data,gameId); });
      });
      controls.appendChild(editBtn);
    }

    div.appendChild(controls);
    commentsWrap.appendChild(div);
  });
  const existingComments=body.querySelector('.comments-wrap'); if(existingComments) existingComments.replaceWith(commentsWrap); else body.appendChild(commentsWrap);
}

async function init(){ try{ const all=await api('all'); const gamesResp=await api('games'); const gamesMeta=gamesResp.games||[]; document.querySelector('#current-user').textContent=all.__user||''; const liste1 = document.querySelector('#liste1-body'); if(liste1){ liste1.innerHTML=''; for(const k of Object.keys(all)){ if(k.startsWith('__')) continue; const g = all[k]; if(!g || (g.list||1) != 1) continue; const tr=document.createElement('tr'); tr.innerHTML = `<td><strong>${esc(g.name)}</strong></td><td>${esc(g.players)}</td><td>${esc(g.genre)}</td><td>${g.steam?`<a href="${esc(g.steam)}" rel="noopener noreferrer" target="_blank" class="steam-link">Steam ↗</a>`:''}</td><td><div class="community-cell" data-community-game="${esc(k)}">Loading…</div></td>`; liste1.appendChild(tr); } }
    const cells=[...document.querySelectorAll('[data-community-game]')];
    for(const cell of cells){
      const key=cell.dataset.communityGame; let data = all[key] || null; if(!data){ const slug=slugifyClient(key); data = all[slug] || null; } if(!data){ for(const kk of Object.keys(all)){ const maybe=all[kk]; if(maybe && String(maybe.name).toLowerCase()===String(key).toLowerCase()){ data=maybe; break; } } }
      const renderData = data || { average:0, count:0, mine:0, vote_details:[], comments:[], admin:false };
      renderCell(cell, renderData);
      const card = cell.querySelector('.community-card');
      if(card){
        const toggle = card.querySelector('.community-toggle');
        if(toggle){ toggle.addEventListener('click', async ()=>{ card.classList.toggle('open'); if(card.classList.contains('open')){ let gid = key; if(all[gid]){} else { const slug=slugifyClient(key); if(all[slug]) gid=slug; else { for(const kk of Object.keys(all)){ const maybe=all[kk]; if(maybe && String(maybe.name).toLowerCase()===String(key).toLowerCase()){ gid=kk; break; } } } }
              await populateBody(card, gid); } }); }
      }
    }
    for(const g of gamesMeta){ const id = g.id; const name = (g.name||'').trim(); let exists=false; if(document.querySelector(`[data-community-game="${CSS.escape(id)}"]`)) exists=true; if(!exists){ const rows = document.querySelectorAll('#liste1-body tr'); for(const r of rows){ const cell = r.querySelector('td'); if(cell && String(cell.textContent).trim().toLowerCase()===name.toLowerCase()){ exists=true; break; } } } if(exists) continue; const listNum = (g.list||1); if(listNum!=1) continue; const tb=document.querySelector('#liste1-body'); if(!tb) continue; const tr=document.createElement('tr'); tr.innerHTML = `<td><strong>${esc(g.name)}</strong></td><td>${esc(g.players)}</td><td>${esc(g.genre)}</td><td>${g.steam?`<a href="${esc(g.steam)}" rel="noopener noreferrer" target="_blank" class="steam-link">Steam ↗</a>`:''}</td><td><div class="community-cell" data-community-game="${esc(g.id)}">Loading…</div></td>`; tb.appendChild(tr); }
  }catch(e){ document.querySelectorAll('[data-community-game]').forEach(c=>c.innerHTML=`<div class="community-status community-error">${esc(e.message)}</div>`); } }

  document.readyState==='loading'?document.addEventListener('DOMContentLoaded',init):init();
})();
</script></head><body><main><div class="userbar">Logged in as <strong id="current-user">…</strong><span class="userbar-sep"> · </span><a href="logout.php">Log out</a><span style="margin-left:10px"></span></div>
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
<tbody id="liste1-body">
<!-- Populated from community-data/community.json -->
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
<tbody id="liste2-body">
<!-- Liste 2 migrated into Liste 1; left empty -->
</tbody>
</table></div><hr/><p>Tea for Two</p><p><a href="https://store.steampowered.com/app/1335790/Operation_Tango/" rel="noopener noreferrer" target="_blank">Operation Tango</a></p></main>
</body></html>
