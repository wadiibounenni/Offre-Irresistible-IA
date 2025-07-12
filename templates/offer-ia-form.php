<?php
if (!defined('ABSPATH')) exit;
?>
<form id="oiai-offer-ia-form" method="post" style="background:#fff;padding:24px;border-radius:8px;max-width:750px;box-shadow:0 2px 8px #e0e0e0;">
    <h2>Générateur d'offre IA (Facebook & Site web)</h2>
    <div class="oiai-step">
        <label for="oiai_ia_product"><strong>1. Produit/service principal</strong></label><br/>
        <input type="text" name="oiai_ia_product" id="oiai_ia_product" style="width:350px" required placeholder="Nom du produit/service principal">
    </div>
    <div class="oiai-step">
        <label for="oiai_ia_price"><strong>2. Prix du produit principal (DT)</strong></label><br/>
        <input type="number" min="0" step="0.01" name="oiai_ia_price" id="oiai_ia_price" style="width:150px" required placeholder="Prix en DT">
    </div>
    <div class="oiai-step">
        <label for="oiai_ia_type"><strong>3. Type d'offre</strong></label><br/>
        <select name="oiai_ia_type" id="oiai_ia_type" style="width:300px">
            <option value="offre limitée dans le temps">Offre limitée dans le temps</option>
            <option value="quantité limitée">Quantité limitée</option>
            <option value="exclusivité">Exclusivité</option>
        </select>
    </div>
    <div class="oiai-step">
        <label><strong>4. Questions sur le produit/service</strong></label><br/>
        <div id="oiai-ia-questions-container"></div>
        <button type="button" id="oiai-ia-load-questions" class="button button-secondary" style="margin-top:8px;">Charger les questions dynamiques</button>
    </div>
    <div class="oiai-step">
        <div id="oiai-ia-bonus-result" style="margin-top:16px;"></div>
    </div>
    <div style="margin-top:28px;">
        <button type="button" id="oiai-ia-generate-btn" class="button button-primary" style="font-size:1.1em;padding:10px 28px;">Générer les textes IA</button>
    </div>
    <div id="oiai-ia-results" style="margin-top:36px;"></div>
</form>
<script>
jQuery(function($){
    let questions = [];
    let selectedBonuses = [];
    let answers = [];
    // Charger dynamiquement les questions (mêmes que Générateur d'offre)
    $('#oiai-ia-load-questions').on('click', function(e){
        e.preventDefault();
        let prod = $('#oiai_ia_product').val();
        let price = $('#oiai_ia_price').val();
        if(!prod) { alert('Merci de saisir un produit/service.'); return; }
        if(!price) { alert('Merci de saisir le prix du produit.'); return; }
        $('#oiai-ia-questions-container').html('<em>Chargement des questions...</em>');
        // 1. Charger les questions dynamiques
        $.post(ajaxurl, {
            action: 'oiai_generate_dynamic_answers',
            oiai_product_name: prod
        }, function(resp){
            if(!resp.success) { $('#oiai-ia-questions-container').html('<span style="color:red">Erreur : '+resp.data+'</span>'); return; }
            questions = resp.data;
            let html = '';
            $.each(questions, function(i, q) {
                html += '<div style="margin-bottom:12px;"><label><strong>'+(i+1)+'. '+q.label+'</strong></label>';
                html += '<div class="oiai-desc">'+q.desc+'</div>';
                html += '<div class="oiai-options" data-q="'+i+'">';
                $.each(q.options, function(j, opt){
                    html += '<label><input type="checkbox" name="oiai_ia_answers['+i+'][]" value="'+opt.replace(/"/g,'&quot;')+'"> '+opt+'</label><br/>';
                });
                html += '</div></div>';
            });
            $('#oiai-ia-questions-container').html(html);
            $('#oiai-ia-load-questions').hide(); // Masquer le bouton après affichage
            // 2. Générer automatiquement les bonus
            $('#oiai-ia-bonus-result').html('<em>Génération des suggestions de bonus...</em>');
            $.post(ajaxurl, {
                action: 'oiai_bundle_bonus_suggestions',
                oiai_bundle_product: prod,
                oiai_bundle_price: price
            }, function(respBonus){
                if(!respBonus.success) {
                    $('#oiai-ia-bonus-result').html('<span style="color:red">Erreur : '+respBonus.data+'</span>');
                    return;
                }
                let data = respBonus.data;
                if(typeof data === 'string') data = JSON.parse(data);
                let html = '<h3>Sélectionnez les bonus à inclure :</h3><div style="display:flex;gap:16px;flex-wrap:wrap;">';
                data.forEach(function(bonus, idx){
                    html += '<div style="flex:1;background:#f9f9ff;border:1px solid #b4c6ff;border-radius:8px;padding:14px;min-width:200px;">'+
                        '<label><input type="checkbox" class="oiai-ia-bonus-select" value="'+idx+'"> <strong>'+bonus.titre+'</strong></label>'+
                        '<div>'+bonus.description+'</div>'+
                        '<div style="color:#1976d2;font-size:0.97em">Valeur estimée : '+bonus.valeur+' DT</div>'+
                    '</div>';
                });
                html += '</div>';
                $('#oiai-ia-bonus-result').html(html);
                selectedBonuses = data;
            });
        });
    });
    // Génération des textes IA
    $('#oiai-ia-generate-btn').on('click', function(e){
        e.preventDefault();
        // Collecte des réponses questions dynamiques
        answers = [];
        $('.oiai-options').each(function(i, el){
            let ans = [];
            $(el).find('input[type=checkbox]:checked').each(function(){
                ans.push($(this).val());
            });
            answers.push(ans.join(' / '));
        });
        let prod = $('#oiai_ia_product').val();
        let price = $('#oiai_ia_price').val();
        let type = $('#oiai_ia_type').val();
        let checked = [];
        $('.oiai-ia-bonus-select:checked').each(function(){
            checked.push(selectedBonuses[$(this).val()]);
        });
        if(!prod || !price) { alert('Merci de remplir le produit et le prix.'); return; }
        if(answers.length === 0) { alert('Merci de charger et répondre aux questions dynamiques.'); return; }
        if(checked.length === 0) { alert('Sélectionnez au moins un bonus.'); return; }
        let totalBonus = checked.reduce((sum, b) => sum + parseFloat(b.valeur), 0);
        let prixNormal = (parseFloat(price) + totalBonus).toFixed(2);
        let prixFinal = price;
        $('#oiai-ia-results').html('<em>Génération des textes IA...</em>');
        $.post(ajaxurl, {
            action: 'oiai_generate_offer_ia',
            oiai_ia_product: prod,
            oiai_ia_price: price,
            oiai_ia_type: type,
            oiai_ia_answers: JSON.stringify(answers),
            oiai_ia_bonus: JSON.stringify(checked),
            oiai_ia_prix_normal: prixNormal,
            oiai_ia_prix_final: prixFinal
        }, function(resp){
            if(!resp.success) {
                $('#oiai-ia-results').html('<span style="color:red">Erreur : '+resp.data+'</span>');
                return;
            }
            let data = resp.data;
            if(typeof data === 'string') data = JSON.parse(data);
            let fbHtml = data.facebook
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Mettre en gras réel
                .replace(/\n/g, '<br>'); // Saut de ligne
            let webHtml = data.siteweb
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
            let html = '<div style="margin-bottom:22px;background:#f7f9ff;padding:16px;border-radius:8px;border:1px solid #b4c6ff;">'+
                '<h3 style="color:#1976d2">Texte Facebook (avec emojis)</h3>'+
                '<div id="oiai-fb-html" style="width:100%;min-height:180px;font-size:1.18em;padding:12px 14px;margin-bottom:8px;border-radius:6px;border:1px solid #b4c6ff;background:#fff;resize:vertical;line-height:1.7;text-align:left;">'+fbHtml+'</div>'+
                '<button class="button button-secondary" type="button" id="oiai-fb-copy-btn">Copier le texte Facebook</button>'+
            '</div>';
            html += '<div style="background:#f7fff7;padding:16px;border-radius:8px;border:1px solid #b7e4c7;">'+
                '<h3 style="color:#388e3c">Texte site web</h3>'+
                '<div id="oiai-web-html" style="width:100%;min-height:220px;font-size:1.18em;padding:12px 14px;margin-bottom:8px;border-radius:6px;border:1px solid #b7e4c7;background:#fff;resize:vertical;line-height:1.7;text-align:left;">'+webHtml+'</div>'+
                '<button class="button button-secondary" type="button" id="oiai-web-copy-btn">Copier le texte site web</button>'+
            '</div>';
            $('#oiai-ia-results').html(html);
            // Copie HTML avec gras
            $('#oiai-fb-copy-btn').on('click', function(){
                let html = document.getElementById('oiai-fb-html').innerHTML;
                navigator.clipboard.write([
                  new window.ClipboardItem({
                    'text/html': new Blob([html], {type: 'text/html'}),
                    'text/plain': new Blob([document.getElementById('oiai-fb-html').innerText], {type: 'text/plain'})
                  })
                ]);
            });
            $('#oiai-web-copy-btn').on('click', function(){
                let html = document.getElementById('oiai-web-html').innerHTML;
                navigator.clipboard.write([
                  new window.ClipboardItem({
                    'text/html': new Blob([html], {type: 'text/html'}),
                    'text/plain': new Blob([document.getElementById('oiai-web-html').innerText], {type: 'text/plain'})
                  })
                ]);
            });
        });
    });
});
</script>
