<?php
if (!defined('ABSPATH')) exit;
?>
<form id="oiai-offer-audit-form" method="post" style="background:#fff;padding:24px;border-radius:8px;max-width:700px;box-shadow:0 2px 8px #e0e0e0;">
    <h2>Audit d'Offre IA</h2>
    <div class="oiai-step">
        <label for="oiai_audit_offer_text"><strong>1. Collez ici l'offre à auditer</strong></label><br/>
        <textarea name="oiai_audit_offer_text" id="oiai_audit_offer_text" rows="7" style="width:100%;max-width:650px" required placeholder="Collez votre offre ici..."></textarea>
    </div>
    <button type="submit" id="oiai-audit-btn" class="button button-primary" style="font-size:1.1em;padding:10px 24px;">Auditer l'offre</button>
    <div id="oiai-offer-audit-result" style="margin-top:30px"></div>
</form>
<script>
jQuery(function($){
    $('#oiai-offer-audit-form').on('submit', function(e){
        e.preventDefault();
        $('#oiai-offer-audit-result').html('<em>Audit en cours...</em>');
        let offer = $('#oiai_audit_offer_text').val();
        $.post(ajaxurl, {
            action: 'oiai_audit_offer',
            oiai_audit_offer_text: offer,
            oiai_audit_tone: 'Neutre'
        }, function(resp){
            if(!resp.success) {
                $('#oiai-offer-audit-result').html('<span style="color:red">Erreur : '+resp.data+'</span>');
                return;
            }
            try {
                let data = resp.data;
                if(typeof data === 'string') data = JSON.parse(data);
                let html = '<div style="display:flex;gap:18px;flex-wrap:wrap;">';
                html += '<div style="flex:1;background:#f9fff9;border:1px solid #b7e4c7;border-radius:8px;padding:16px;min-width:200px;">'+
                    '<h3 style="color:#388e3c">Points forts</h3><ul>' +
                    (data.points_forts||[]).map(pt=>'<li>'+pt+'</li>').join('') + '</ul></div>';
                html += '<div style="flex:1;background:#fff8f8;border:1px solid #ffb4b4;border-radius:8px;padding:16px;min-width:200px;">'+
                    '<h3 style="color:#d32f2f">Points faibles / Risques</h3><ul>' +
                    (data.points_faibles||[]).map(pt=>'<li>'+pt+'</li>').join('') + '</ul></div>';
                html += '<div style="flex:1;background:#f9f9ff;border:1px solid #b4c6ff;border-radius:8px;padding:16px;min-width:200px;">'+
                    '<h3 style="color:#1976d2">Conseils d\'amélioration</h3><ul>' +
                    (data.conseils||[]).map(pt=>'<li>'+pt+'</li>').join('') + '</ul></div>';
                html += '</div>';
                $('#oiai-offer-audit-result').html(html);
            } catch(ex) {
                $('#oiai-offer-audit-result').html('<span style="color:red">Erreur de parsing de la réponse.</span>');
            }
        });
    });
});
</script>
