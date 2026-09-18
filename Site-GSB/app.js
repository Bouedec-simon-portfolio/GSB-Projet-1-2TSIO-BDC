(function(){
  const users = {
    "sio": { password: "password", name: "SIO" },
    "louis": { password: "gsb123", name: "Louis Villechalane" },
    "admin": { password: "admin", name: "Administrateur" }
  };

  function setUser(id){
    const u = users[id] ? { id, name: users[id].name } : { id, name: id };
    sessionStorage.setItem('gsbUser', JSON.stringify(u));
  }

  function getUser(){
    try{ return JSON.parse(sessionStorage.getItem('gsbUser')); }catch(e){ return null; }
  }

  function clearUser(){ sessionStorage.removeItem('gsbUser'); }

  let gsbFiches = [];

  const DEFAULT_SVG = `<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120'><rect width='100%' height='100%' fill='#e85d04'/><text x='50%' y='55%' font-size='40' font-family='Arial,Helvetica,sans-serif' fill='white' text-anchor='middle' dominant-baseline='middle'>GSB</text></svg>`;
  const DEFAULT_PP = 'data:image/svg+xml;utf8,' + encodeURIComponent(DEFAULT_SVG);

  function loadFiches(){
    return gsbFiches;
  }

  function saveFiches(fiches){ 
    gsbFiches = fiches; 
  }

  function getFicheById(id){ const f = loadFiches(); return f.find(x=>x.id===id); }
  function upsertFiche(fiche){ const all = loadFiches(); const idx = all.findIndex(x=>x.id===fiche.id); if(idx>=0){ all[idx]=fiche; } else { all.unshift(fiche); } saveFiches(all); }
  function deleteFiche(id){ let all = loadFiches(); all = all.filter(x=>x.id!==id); saveFiches(all); }

  function addLineToFiche(ficheId, line){ const f = getFicheById(ficheId); if(!f) return false; line.id = Date.now(); f.items.push(line); f.justificatifs = f.items.length; f.modified = (new Date()).toISOString().slice(0,10); upsertFiche(f); return true; }

  function removeLineFromFiche(ficheId, lineId){ const f = getFicheById(ficheId); if(!f) return false; f.items = f.items.filter(l=>l.id!=lineId); f.justificatifs = f.items.length; f.modified = (new Date()).toISOString().slice(0,10); upsertFiche(f); return true; }

  function totalFiche(f){ return (f.items||[]).reduce((s,i)=>s + (parseFloat(i.montant)||0),0); }

  document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('loginForm');
    if(form){
      form.addEventListener('submit', function(e){
        e.preventDefault();
        const id = (document.getElementById('id')||{}).value.trim();
        const pwd = (document.getElementById('pwd')||{}).value;
        const err = document.getElementById('login-error');
        if(!id || !pwd){ if(err) err.textContent = 'Veuillez renseigner identifiant et mot de passe.'; return; }
        const user = users[id.toLowerCase()];
        if(user && user.password === pwd){
          setUser(id.toLowerCase());
          window.location.href = 'tableau-bord.html';
        } else {
          if(err) err.textContent = 'Identifiant ou mot de passe incorrect.'; else alert('Identifiant ou mot de passe incorrect.');
        }
      });
    }

    const futureForm = document.getElementById('futureLoginForm');
    if(futureForm){
      futureForm.addEventListener('submit', function(e){
        e.preventDefault();
        const id = (document.getElementById('future-id')||{}).value.trim();
        const pwd = (document.getElementById('future-pwd')||{}).value;
        const err = document.getElementById('future-login-error');
        if(!id || !pwd){ if(err) err.textContent = 'Veuillez renseigner identifiant et mot de passe.'; return; }
        if(err) err.textContent = 'Cette page est en cours de développement. Vous serez redirigé bientôt.';
        setTimeout(function(){ alert('Page en cours de développement - Bienvenue ' + id); }, 800);
      });
    }

    const headerUser = document.getElementById('header-user');
    const logoutLink = document.getElementById('logout-link');
    if(headerUser || logoutLink){
      const current = getUser();
      if(!current){
        window.location.replace('connexion.html');
        return;
      }
      if(headerUser) headerUser.textContent = current.name || current.id;
      const headerRole = document.getElementById('header-role');
      if(headerRole) headerRole.textContent = (current.id === 'admin') ? 'Administrateur' : 'Collaborateur';
      const ppContainer = document.getElementById('user-pp');
      const ppImg = document.getElementById('user-pp-img');
      if(ppContainer){
        if(ppImg) ppImg.style.display = 'block';
        const saved = localStorage.getItem('gsb_pp_'+(current.id||current.name));
        if(saved && ppImg){
          ppImg.src = saved;
          ppContainer.textContent = '';
        } else if(ppImg){
          ppImg.src = DEFAULT_PP;
          ppContainer.textContent = '';
        } else {
          ppContainer.textContent = 'GSB';
        }
      }
      const welcomeH2 = document.querySelector('.welcome-text h2');
      if(welcomeH2) welcomeH2.textContent = 'Bienvenue ' + (current.name || current.id);
      if(logoutLink){
        logoutLink.addEventListener('click', function(e){
          e.preventDefault();
          clearUser();
          window.location.href = 'connexion.html';
        });
      }
    }

    if(document.getElementById('fiches-tbody')){
      const tbody = document.getElementById('fiches-tbody');
      const filter = document.getElementById('filter-month');
      const newBtn = document.getElementById('new-fiche');

      function renderFiches(){
        const fiches = loadFiches();
        tbody.innerHTML = '';
        const months = Array.from(new Set(fiches.map(f=>f.month)));
        filter.innerHTML = '<option value="all">Tous</option>' + months.map(m=>`<option value="${m}">${m}</option>`).join('');
        const sel = filter.value || 'all';
        const shown = fiches.filter(f=> sel==='all' ? true : f.month===sel );
        if(shown.length === 0){
          tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:#999;">Aucune fiche de frais créée. Cliquez sur "Nouvelle Fiche" pour en créer une.</td></tr>';
          return;
        }
        shown.forEach(f=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `<td>${f.month}</td><td><span class="badge">${f.state}</span> ${f.state==='CR'?'Saisie en cours':'Clôturée'}</td><td class="num">${f.justificatifs||0}</td><td class="num">${totalFiche(f).toFixed(2)} €</td><td>${f.modified||''}</td><td><a href="consultation-fiches.html" class="btn-grey" data-id="${f.id}" onclick="event.preventDefault(); sessionStorage.setItem('gsb_view_fiche', '${f.id}'); window.location='consultation-fiches.html';">Voir</a></td>`;
          tbody.appendChild(tr);
        });
      }
      filter.addEventListener('change', renderFiches);
      newBtn.addEventListener('click', function(){
        const month = prompt('Mois (YYYYMM) pour la nouvelle fiche (vous serez redirigé) :');
        if(!month) return;
        sessionStorage.setItem('gsb_new_fiche', month);
        window.location.href = 'saisie-frais.html';
      });
      renderFiches();
    }

    if(document.getElementById('consult-fiches-tbody')){
      const tbody = document.getElementById('consult-fiches-tbody');
      const detailBox = document.getElementById('fiche-detail');
      const detailTbody = document.getElementById('detail-lines-tbody');
      const detailBack = document.getElementById('detail-back');

      function renderList(){
        const fiches = loadFiches(); tbody.innerHTML = '';
        if(fiches.length === 0){
          tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:#999;">Aucune fiche de frais à consulter.</td></tr>';
          return;
        }
        fiches.forEach(f=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `<td>${f.month}</td><td>${f.state}</td><td class="num">${f.justificatifs||0}</td><td class="num">${totalFiche(f).toFixed(2)} €</td><td>${f.modified||''}</td><td><button class="btn-grey" data-id="${f.id}">Voir</button></td>`;
          tbody.appendChild(tr);
        });
        tbody.querySelectorAll('button[data-id]').forEach(btn=> btn.addEventListener('click', function(){ const id=this.dataset.id; showDetail(id); }));
      }
      function showDetail(id){ const f = getFicheById(id); if(!f) return; detailTbody.innerHTML = ''; f.items.forEach(it=>{ const tr=document.createElement('tr'); tr.innerHTML = `<td>${it.date}</td><td>${it.libelle}</td><td class="num">${parseFloat(it.montant).toFixed(2)} €</td>`; detailTbody.appendChild(tr); }); detailBox.style.display='block'; }
      detailBack.addEventListener('click', function(){ detailBox.style.display='none'; });
      renderList();
      const viewId = sessionStorage.getItem('gsb_view_fiche'); if(viewId){ sessionStorage.removeItem('gsb_view_fiche'); showDetail(viewId); }
    }

    if(document.getElementById('saisie-select-month')){
      const sel = document.getElementById('saisie-select-month');
      const tbody = document.getElementById('saisie-lines-tbody');
      const sub = document.getElementById('current-fiche-subtitle');
      const newBtn = document.getElementById('new-fiche-saisie');
      const addBtn = document.getElementById('add-line-btn');

      function renderMonths(){ const fiches = loadFiches(); if(fiches.length === 0){ sel.innerHTML = '<option>Aucune fiche</option>'; sub.textContent='Fiche de frais pour : (aucune)'; tbody.innerHTML=''; return; } sel.innerHTML = fiches.map(f=>`<option value="${f.id}">${f.month}</option>`).join(''); if(fiches.length) sel.value=fiches[0].id; renderLines(); }
      function renderLines(){ const f = getFicheById(sel.value); tbody.innerHTML=''; if(!f){ sub.textContent='Fiche de frais pour : (aucune)'; return; } sub.textContent='Fiche de frais pour : '+f.month; (f.items||[]).forEach(it=>{ const tr=document.createElement('tr'); tr.innerHTML = `<td>${it.date}</td><td>${it.libelle}</td><td class="num">${parseFloat(it.montant).toFixed(2)} €</td><td><button class="btn-grey" data-id="${it.id}">Suppr</button></td>`; tbody.appendChild(tr); }); tbody.querySelectorAll('button[data-id]').forEach(b=> b.addEventListener('click', function(){ if(confirm('Supprimer cette ligne ?')){ removeLineFromFiche(sel.value, this.dataset.id); renderLines(); } })); }
      newBtn.addEventListener('click', function(){ const month = prompt('Mois (YYYYMM) pour la nouvelle fiche :'); if(!month) return; const fiche = { id: month, month, state:'CR', justificatifs:0, modified:(new Date()).toISOString().slice(0,10), items:[] }; upsertFiche(fiche); renderMonths(); });
      addBtn.addEventListener('click', function(){ const date = document.getElementById('line-date').value; const lib = document.getElementById('line-libelle').value; const mont = document.getElementById('line-montant').value; if(!date || !lib || !mont){ alert('Remplissez date, libellé et montant'); return; } addLineToFiche(sel.value, {date, libelle:lib, montant: parseFloat(mont)}); document.getElementById('line-libelle').value=''; document.getElementById('line-montant').value=''; renderLines(); });
      sel.addEventListener('change', renderLines);
      renderMonths();
      const pending = sessionStorage.getItem('gsb_new_fiche');
      if(pending){
        sessionStorage.removeItem('gsb_new_fiche');
        if(!getFicheById(pending)){
          const fiche = { id: pending, month: pending, state:'CR', justificatifs:0, modified:(new Date()).toISOString().slice(0,10), items:[] };
          upsertFiche(fiche);
          renderMonths();
          sel.value = fiche.id;
          renderLines();
          alert('Fiche créée : '+pending);
        } else {
          sel.value = pending; renderLines();
        }
      }
    }
  });
})();
