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
.community-stars{display:inline-flex;gap:1px}.community-stars button{border:0;background:none;padding:0 2px;font-size:1.35rem;line-height:1;color:#cbd2dc;cursor:pointer}.community-stars button:hover,.community-stars button.selected{color:#f5b301}
.community-rating{color:#596273;white-space:nowrap}.community-toggle{border:1px solid #cfd6e1;background:#fff;color:#374151;border-radius:7px;padding:4px 8px;cursor:pointer;margin-left:auto}.community-toggle:hover{background:#f0f4f8}
.community-body{display:none;margin-top:10px;padding-top:10px;border-top:1px solid #e5e9ef}.community-card.open .community-body{display:block}
.community-form{display:grid;gap:7px;margin-bottom:10px}.community-form input,.community-form textarea{font:inherit;width:100%;padding:8px 10px;border:1px solid #cbd2dc;border-radius:7px;background:#fff;color:#1f2937}.community-form textarea{min-height:72px;resize:vertical}
.community-form button[type=submit]{justify-self:start;border:0;border-radius:7px;padding:7px 11px;background:#2563eb;color:#fff;cursor:pointer}.community-form button[type=submit]:hover{background:#1d4ed8}
.community-comment{padding:8px 0;border-top:1px solid #e5e9ef}.community-comment-meta{font-size:.78rem;color:#6b7280;margin-bottom:2px}.community-comment-text{white-space:pre-wrap;overflow-wrap:anywhere}.community-empty,.community-status{font-size:.85rem;color:#6b7280}.community-error{color:#b91c1c}
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
 async function api(action, options={}){const r=await fetch(`${API}?action=${encodeURIComponent(action)}`,{credentials:'same-origin',headers:{'Accept':'application/json',...(options.body?{'Content-Type':'application/json'}:{})},...options});const t=await r.text();let d;try{d=JSON.parse(t)}catch(e){throw new Error(`Server did not return JSON (HTTP ${r.status}).`)}if(!r.ok||d.error)throw new Error(d.error||`HTTP ${r.status}`);return d}
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const date=v=>{const d=new Date(v);return Number.isNaN(d.getTime())?'':d.toLocaleString()};
 function stars(game,avg,mine){const s=mine||Math.round(Number(avg)||0);return `<div class="community-stars">${[1,2,3,4,5].map(n=>`<button type="button" data-rate="${n}" aria-label="${n} star${n===1?'':'s'}" class="${s>=n?'selected':''}">★</button>`).join('')}</div>`}
 function adminPanel(game,data){if(!data.admin)return '';let votes=(data.vote_details||[]).map(v=>`<div class="admin-item"><span>⭐ ${v.rating} · ${esc(v.id)}</span><button type="button" data-delete-vote="${esc(v.id)}">Delete vote</button></div>`).join('');return `<div class="admin-panel"><strong>Admin controls</strong><div class="admin-sub">Individual votes</div>${votes||'<div class="community-empty">No votes.</div>'}</div>`}
 function render(cell,data){const game=cell.dataset.communityGame;cell.innerHTML=`<div class="community-card"><div class="community-summary">${stars(game,data.average,data.mine)}<span class="community-rating">${Number(data.average||0).toFixed(1)}/5 · ${data.votes} vote${data.votes===1?'':'s'}</span><button type="button" class="community-toggle">Comments (${data.comments.length})</button></div><div class="community-body"><form class="community-form"><textarea name="comment" maxlength="2000" required placeholder="Add a comment…"></textarea><button type="submit">Post comment</button></form><div class="community-comments">${data.comments.length?data.comments.map(c=>`<article class="community-comment"><div class="community-comment-meta">${esc(c.name||'Anonymous')} · ${date(c.created_at)} ${data.admin?`<button type="button" class="admin-delete" data-delete-comment="${esc(c.id)}">Delete</button>`:''}</div><div class="community-comment-text">${esc(c.comment)}</div></article>`).join(''):'<div class="community-empty">No comments yet.</div>'}</div>${adminPanel(game,data)}</div></div>`;const card=cell.querySelector('.community-card');cell.querySelector('.community-toggle').onclick=()=>card.classList.toggle('open');cell.querySelectorAll('[data-rate]').forEach(b=>b.onclick=async()=>{try{render(cell,await api('vote',{method:'POST',body:JSON.stringify({game,rating:+b.dataset.rate})}));cell.querySelector('.community-card').classList.add('open')}catch(e){alert(e.message)}});cell.querySelector('form').onsubmit=async e=>{e.preventDefault();const f=e.currentTarget;try{render(cell,await api('comment',{method:'POST',body:JSON.stringify({game,comment:f.comment.value})}));cell.querySelector('.community-card').classList.add('open');f.reset()}catch(e){alert(e.message)}};cell.querySelectorAll('[data-delete-comment]').forEach(b=>b.onclick=async()=>{if(!confirm('Delete this comment?'))return;try{render(cell,await api('delete_comment',{method:'POST',body:JSON.stringify({game,id:b.dataset.deleteComment})}));cell.querySelector('.community-card').classList.add('open')}catch(e){alert(e.message)}});cell.querySelectorAll('[data-delete-vote]').forEach(b=>b.onclick=async()=>{if(!confirm('Delete this vote?'))return;try{render(cell,await api('delete_vote',{method:'POST',body:JSON.stringify({game,id:b.dataset.deleteVote})}));cell.querySelector('.community-card').classList.add('open')}catch(e){alert(e.message)}})}
 async function init(){const cells=[...document.querySelectorAll('[data-community-game]')];try{const all=await api('all');document.querySelector('#current-user').textContent=all.__user||'';if(all.__admin)document.querySelector('#admin-link').style.display='inline';cells.forEach(c=>render(c,all[c.dataset.communityGame]||{average:0,votes:0,mine:0,comments:[],admin:false}))}catch(e){cells.forEach(c=>c.innerHTML=`<div class="community-status community-error">${esc(e.message)}</div>`);}}
 document.readyState==='loading'?document.addEventListener('DOMContentLoaded',init):init();
})();
</script></head><body><main><div class="userbar">Logged in as <strong id="current-user">…</strong><span class="userbar-sep"> · </span><a href="logout.php">Log out</a><a href="admin.php" id="admin-link" style="display:none">Admin</a></div><h1>Games für die Freigeister</h1><p class="intro">Game list with community ratings and comments.</p><h2>Liste 1</h2><div class="table-wrap"><table>
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
<tr>
<td><strong>Valheim</strong></td>
<td>10</td>
<td>Survival / RPG</td>
<td><a href="https://store.steampowered.com/app/892970/Valheim/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="valheim">Loading…</div></td>
</tr>
<tr>
<td><strong>Enshrouded</strong></td>
<td>16</td>
<td>Survival elements / Action RPG</td>
<td><a href="https://store.steampowered.com/app/1203620/Enshrouded/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="enshrouded">Loading…</div></td>
</tr>
<tr>
<td><strong>Barotrauma</strong></td>
<td>16</td>
<td>2D Co-op survival / submarine</td>
<td><a href="https://store.steampowered.com/app/602960/Barotrauma/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="barotrauma">Loading…</div></td>
</tr>
<tr>
<td><strong>R.E.P.O.</strong></td>
<td>6</td>
<td>Horror / Co-op</td>
<td><a href="https://store.steamp.com/app/3241660/REPO/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="r.e.p.o.">Loading…</div></td>
</tr>
<tr>
<td><strong>Project Zomboid</strong></td>
<td>32+</td>
<td>Zombie survival / simulation</td>
<td><a href="https://store.steampowered.com/app/108600/Project_Zomboid/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="project zomboid">Loading…</div></td>
</tr>
<tr>
<td><strong>Sons of the Forest</strong></td>
<td>8</td>
<td>Survival / horror</td>
<td><a href="https://store.steampowered.com/app/1326470/Sons_of_the_Forest/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="sons of the forest">Loading…</div></td>
</tr>
<tr>
<td><strong>Terraria</strong></td>
<td>8+</td>
<td>Sandbox / RPG</td>
<td><a href="https://store.steampowered.com/app/105600/Terraria/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="terraria">Loading…</div></td>
</tr>
<tr>
<td><strong>Core Keeper</strong></td>
<td>2-8</td>
<td>Survival / RPG</td>
<td><a href="https://store.steampowered.com/app/1621690/Core_Keeper/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="core keeper">Loading…</div></td>
</tr>
<tr>
<td><strong>Conan Exiles</strong></td>
<td>40+</td>
<td>Survival / RPG</td>
<td><a href="https://store.steampowered.com/app/440900/Conan_Exiles/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="conan exiles">Loading…</div></td>
</tr>
<tr>
<td><strong>ARK: Survival Ascended</strong></td>
<td>8 private / 70 public</td>
<td>Survival / dinosaurs</td>
<td><a href="https://store.steampowered.com/app/2399830/ARK_Survival_Ascended/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="ark: survival ascended">Loading…</div></td>
</tr>
<tr>
<td><strong>Factorio</strong></td>
<td>6+</td>
<td>Factory / strategy</td>
<td><a href="https://store.steampowered.com/app/427520/Factorio/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="factorio">Loading…</div></td>
</tr>
<tr>
<td><strong>Satisfactory</strong></td>
<td>6+</td>
<td>Factory / exploration</td>
<td><a href="https://store.steampowered.com/app/526870/Satisfactory/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="satisfactory">Loading…</div></td>
</tr>
<tr>
<td><strong>Don't Starve Together</strong></td>
<td>6+</td>
<td>Survival</td>
<td><a href="https://store.steampowered.com/app/322330/Dont_Starve_Together/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="don't starve together">Loading…</div></td>
</tr>
<tr>
<td><strong>Palworld</strong></td>
<td>32 dedicated server</td>
<td>Survival / creature collection</td>
<td><a href="https://store.steampowered.com/app/1623730/Palworld/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="palworld">Loading…</div></td>
</tr>
<tr>
<td><del><strong> Destiny 2</strong></del></td>
<td><del>6-person activities</del></td>
<td><del>FPS / MMO</del></td>
<td><del><a href="https://store.steampowered.com/app/1085660/Destiny_2/" rel="noopener noreferrer" target="_blank">Steam ↗</a></del></td>
<td>Sad Penguin :(<div class="community-cell" data-community-game="destiny 2">Loading…</div></td>
</tr>
<tr>
<td><del><strong>Final Fantasy XIV</strong></del></td>
<td><del>4/8-person parties / larger raids</del></td>
<td><del>MMORPG</del></td>
<td><del><a href="https://store.steampowered.com/app/39210/FINAL_FANTASY_XIV_Online/" rel="noopener noreferrer" target="_blank">Steam ↗</a></del></td>
<td><div class="community-cell" data-community-game="final fantasy xiv">Loading…</div></td>
</tr>
<tr>
<td><del><strong>The Elder Scrolls Online</strong></del></td>
<td><del>4 / 12</del></td>
<td><del>MMORPG</del></td>
<td><del><a href="https://store.steampowered.com/app/306130/The_Elder_Scrolls_Online/" rel="noopener noreferrer" target="_blank">Steam ↗</a></del></td>
<td><div class="community-cell" data-community-game="the elder scrolls online">Loading…</div></td>
</tr>
<tr>
<td><del><strong>World of Warcraft</strong></del></td>
<td><del>5</del></td>
<td><del>MMORPG</del></td>
<td><del>No Steam version</del></td>
<td><div class="community-cell" data-community-game="world of warcraft">Loading…</div></td>
</tr>
<tr>
<td><strong>Killing Floor 3</strong></td>
<td>6</td>
<td>PvE FPS</td>
<td><a href="https://store.steampowered.com/app/1430190/Killing_Floor_3/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="killing floor 3">Loading…</div></td>
</tr>
<tr>
<td><strong>V Rising</strong></td>
<td>40+ private server</td>
<td>Survival / action RPG</td>
<td><a href="https://store.steampowered.com/app/1604030/V_Rising/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="v rising">Loading…</div></td>
</tr>
<tr>
<td><del><strong>Grounded</strong></del></td>
<td><del>4 normally / larger dedicated setups</del></td>
<td><del>Survival</del></td>
<td><del><a href="https://store.steampowered.com/app/962130/Grounded/" rel="noopener noreferrer" target="_blank">Steam ↗</a></del></td>
<td><div class="community-cell" data-community-game="grounded">Loading…</div></td>
</tr>
<tr>
<td><del><strong>Eco</strong></del></td>
<td><del>Large servers</del></td>
<td><del>Survival / civilization</del></td>
<td><a href="https://store.steampowered.com/app/382310/Eco/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="eco">Loading…</div></td>
</tr>
<tr>
<td><strong>Raft</strong></td>
<td>8</td>
<td>Survival / exploration</td>
<td><a href="https://store.steampowered.com/app/648800/Raft/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="raft">Loading…</div></td>
</tr>
<tr>
<td><strong>Astroneer</strong></td>
<td>8</td>
<td>Space exploration / survival</td>
<td><a href="https://store.steampowered.com/app/361420/ASTRONEER/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="astroneer">Loading…</div></td>
</tr>
<tr>
<td><strong>Space Engineers</strong></td>
<td>Large dedicated servers</td>
<td>Space sandbox / engineering</td>
<td><a href="https://store.steampowered.com/app/244850/Space_Engineers/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="space engineers">Loading…</div></td>
</tr>
<tr>
<td><strong> 7 Days to Die</strong></td>
<td>8+</td>
<td>Zombie  survivalcraft</td>
<td><a href="https://store.steampowered.com/app/251570/7_Days_to_Die/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="7 days to die">Loading…</div></td>
</tr>
<tr>
<td><strong>Backrooms: Escape Together</strong></td>
<td>6</td>
<td>Horror / exploration</td>
<td><a href="https://store.steampowered.com/app/2141730/Backrooms_Escape_Together/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="backrooms: escape together">Loading…</div></td>
</tr>
<tr>
<td><strong>The Isle</strong></td>
<td>Large servers</td>
<td>Dinosaur survival</td>
<td><a href="https://store.steampowered.com/app/376210/The_Isle/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="the isle">Loading…</div></td>
</tr>
<tr>
<td><strong>Foxhole</strong></td>
<td>Hundreds</td>
<td>MMO / war strategy</td>
<td><a href="https://store.steampowered.com/app/505460/Foxhole/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="foxhole">Loading…</div></td>
</tr>
<tr>
<td><strong>Dune Awakening</strong></td>
<td>6+</td>
<td>Action, Adventure, Massively Multiplayer, RPG</td>
<td><a href="https://store.steampowered.com/app/1172710/Dune_Awakening/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="dune awakening">Loading…</div></td>
</tr>
<tr>
<td><strong>Fallout 76</strong></td>
<td>8 on private server</td>
<td>Action, Multiplayer, RPG</td>
<td><a href="https://store.steampowered.com/app/1151340/Fallout_76/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="fallout 76">Loading…</div></td>
</tr>
<tr>
<td><strong>Big Walk</strong></td>
<td>6+</td>
<td>Action, Adventure, Talking</td>
<td><a href="https://store.steampowered.com/app/1478500/Big_Walk/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="big walk">Loading…</div></td>
</tr>
<tr>
<td><strong>Warhammer 40 k Darktide</strong></td>
<td>6</td>
<td>Action</td>
<td><a href="https://store.steampowered.com/app/1361210/Warhammer_40000_Darktide/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="warhammer 40 k darktide">Loading…</div></td>
</tr>
<tr>
<td><strong>Marvel Rivals</strong></td>
<td>6 non ranked</td>
<td>MOBA Action</td>
<td><a href="https://store.steampowered.com/app/2767030/Marvel_Rivals/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><a>Steam</a><div class="community-cell" data-community-game="marvel rivals">Loading…</div></td>
</tr>
<tr>
<td><strong>Runescape Dragonwilds</strong></td>
<td>4-6</td>
<td>Open World Action RPG</td>
<td><a href="https://store.steampowered.com/app/1374490/RuneScape_Dragonwilds/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="runescape dragonwilds">Loading…</div></td>
</tr>
<tr>
<td><strong>Foundry Virtual Tabletop</strong></td>
<td>Digital Tabletop RPG Server</td>
<td><a>Adventures in Greyhawk</a></td>
<td>no Steam Page</td>
<td><div class="community-cell" data-community-game="foundry virtual tabletop">Loading…</div></td>
</tr>
<tr>
<td><strong>Palworld</strong></td>
<td>5+ private servers</td>
<td>Large-scale casual co-op sandbox</td>
<td><a href="https://store.steampowered.com/app/1623730/Palworld/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="palworld">Loading…</div></td>
</tr>
<tr>
<td><strong>Nightingale</strong></td>
<td>6</td>
<td>Realm-based cooperative survival</td>
<td><a href="https://store.steampowered.com/app/1928980/Nightingale/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="nightingale">Loading…</div></td>
</tr>
<tr>
<td><strong>Deep Rock Galactic</strong></td>
<td>6</td>
<td>Horde, Graben-Entdecken-Flüchten</td>
<td><a href="https://store.steampowered.com/app/548430/Deep_Rock_Galactic/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="deep rock galactic">Loading…</div></td>
</tr>
<tr>
<td><strong>Warhammer 40K Rogue Trader</strong></td>
<td>6</td>
<td>Ähnlich Baldurs Gate 3, nur Warhammer</td>
<td><a href="https://store.steampowered.com/app/2186680/Warhammer_40000_Rogue_Trader/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="warhammer 40k rogue trader">Loading…</div></td>
</tr>
<tr>
<td><strong>Void Crew</strong></td>
<td>2-6</td>
<td>Space Multi-Crew Ship Salvage &amp; Combat</td>
<td><a href="https://store.steampowered.com/app/1063420/Void_Crew/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="void crew">Loading…</div></td>
</tr>
<tr>
<td><strong>Deadlock</strong></td>
<td>6 v 6</td>
<td>Like TF 2?</td>
<td><a href="https://store.steampowered.com/app/1422450/Deadlock/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="deadlock">Loading…</div></td>
</tr>
</tbody>
</table></div><h2>Liste 2 – MMOs</h2><div class="table-wrap"><table>
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
<tr>
<td><del><strong>The Lord of the Rings Online </strong></del></td>
<td><del><strong>✅ Yes </strong></del></td>
<td><del><strong> 6-player Fellowship</strong></del></td>
<td>~~<a href="https://store.steampowered.com/app/212500/the_lord_of_the_rings_online/" rel="noopener noreferrer" target="_blank">Steam ↗</a> ~~</td>
<td><div class="community-cell" data-community-game="the lord of the rings online">Loading…</div></td>
</tr>
<tr>
<td><del><strong>Final Fantasy XIV </strong></del></td>
<td><del><strong>✅ No</strong></del></td>
<td><del>4/8-player party</del></td>
<td><del><a href="https://store.steampowered.com/app/39210/FINAL_FANTASY_XIV_Online/" rel="noopener noreferrer" target="_blank">Steam ↗</a></del></td>
<td>:(<div class="community-cell" data-community-game="final fantasy xiv">Loading…</div></td>
</tr>
<tr>
<td><strong>The Elder Scrolls Online</strong></td>
<td><strong>✅ Yes</strong></td>
<td>4-player groups / 12-player Trials</td>
<td><a href="https://store.steampowered.com/app/306130/The_Elder_Scrolls_Online/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="the elder scrolls online">Loading…</div></td>
</tr>
<tr>
<td><strong>Guild Wars 2 </strong></td>
<td><strong>✅ Yes</strong></td>
<td>5-player party / 50-player squad</td>
<td><a href="https://store.steampowered.com/app/1284210/Guild_Wars_2/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="guild wars 2">Loading…</div></td>
</tr>
<tr>
<td><strong>EVE Online</strong></td>
<td><strong>✅Yes</strong></td>
<td>Fleets</td>
<td><a href="https://store.steampowered.com/app/8500/EVE_Online/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="eve online">Loading…</div></td>
</tr>
<tr>
<td><del><strong>Old School RuneScape </strong></del></td>
<td><del><strong>✅No</strong></del></td>
<td><del>Flexible</del></td>
<td><del><a href="https://store.steampowered.com/app/1343370/Old_School_RuneScape/" rel="noopener noreferrer" target="_blank">Steam ↗</a></del></td>
<td><div class="community-cell" data-community-game="old school runescape">Loading…</div></td>
</tr>
<tr>
<td><strong>Albion Online </strong></td>
<td><strong>✅</strong></td>
<td>Flexible / large groups</td>
<td><a href="https://store.steampowered.com/app/761890/Albion_Online/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="albion online">Loading…</div></td>
</tr>
<tr>
<td><del><strong>Star Wars: The Old Republic</strong></del></td>
<td><del><strong>⚠️No</strong></del></td>
<td><del><strong>4/8/12-player group</strong></del></td>
<td><del><strong>No Steam version</strong></del></td>
<td><div class="community-cell" data-community-game="star wars: the old republic">Loading…</div></td>
</tr>
<tr>
<td><strong>Lost Ark</strong></td>
<td><strong>✅</strong></td>
<td>4/8-player content</td>
<td><a href="https://store.steampowered.com/app/1599340/Lost_Ark/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="lost ark">Loading…</div></td>
</tr>
<tr>
<td><del>Ashes of Creation</del></td>
<td><del><strong>✅Yes</strong></del></td>
<td><del>6/12/24</del></td>
<td></td>
<td><div class="community-cell" data-community-game="ashes of creation">Loading…</div></td>
</tr>
<tr>
<td><strong>Throne of Liberty</strong></td>
<td><strong>✅Yes</strong></td>
<td>6/12/24</td>
<td><a href="https://store.steampowered.com/app/2429640/Throne_and_Liberty/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="throne of liberty">Loading…</div></td>
</tr>
<tr>
<td><strong>Pantheon Rise of the Fallen</strong></td>
<td><strong>✅Yes</strong></td>
<td>Everquest Style</td>
<td><a href="https://store.steampowered.com/app/3107230/Pantheon_Rise_of_the_Fallen/" rel="noopener noreferrer" target="_blank">Steam ↗</a></td>
<td><div class="community-cell" data-community-game="pantheon rise of the fallen">Loading…</div></td>
</tr>
<tr>
<td><strong>Monsters and Memories </strong></td>
<td><strong>✅Yes</strong></td>
<td>Old School MMO Style</td>
<td><a>October 26</a></td>
<td><div class="community-cell" data-community-game="monsters and memories">Loading…</div></td>
</tr>
</tbody>
</table></div><hr/><p>Tea for Two</p><p><a href="https://store.steampowered.com/app/1335790/Operation_Tango/" rel="noopener noreferrer" target="_blank">Operation Tango</a></p></main>
</body></html>