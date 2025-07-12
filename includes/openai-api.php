<?php
if (!defined('ABSPATH')) exit;

function oiai_generate_offer($answers, $product_name) {
    $api_key = get_option('oiai_openai_api_key');
    if (!$api_key) {
        return 'Clé API OpenAI manquante. Veuillez la renseigner dans les réglages.';
    }
    $prompt = oiai_build_prompt($answers, $product_name);
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un expert en copywriting et offres irrésistibles.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 400,
            'temperature' => 0.8,
        ]),
        'timeout' => 30,
    ]);
    if (is_wp_error($response)) {
        return 'Erreur lors de la connexion à OpenAI.';
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($body['choices'][0]['message']['content'])) {
        return $body['choices'][0]['message']['content'];
    }
    return 'Erreur lors de la génération de l\'offre.';
}

function oiai_build_prompt($answers, $product_name) {
    $prompt = "Produit/service : $product_name\n";
    foreach ($answers as $i => $answer) {
        $prompt .= "Q" . ($i+1) . ": $answer\n";
    }
    $prompt .= "\nÀ partir de ces informations, génère une offre irrésistible en utilisant toutes les réponses sélectionnées, de façon fluide, convaincante et percutante. Intègre chaque réponse dans l'offre finale sans en omettre aucune, et veille à ce que le texte soit engageant, naturel et cohérent. Ne reformule pas les réponses, mais intègre-les telles quelles dans le texte.\n";
    $prompt .= "👉 [Nom de l’offre] Une solution conçue pour [résultat] en [temps], même si [obstacle/frustration], grâce à [ta méthode ou accompagnement].\n✅ Bonus : [bonus 1, bonus 2, etc.]\n🔁 Garantie : [garantie claire]\n⏳ Offre limitée à [x clients / x jours / etc.]\n📍 [Call-to-action final]\n";
    $prompt .= "Utilise absolument toutes les réponses dans la rédaction. N’invente rien qui ne soit pas dans les réponses.\n";
    return $prompt;
}

// Génère dynamiquement 5 suggestions pour une question donnée sur un produit/service (format tableau)
function oiai_generate_dynamic_answers_for_question($product_name, $question, $desc = '') {
    $api_key = get_option('oiai_openai_api_key');
    if (!$api_key) return ["Veuillez renseigner la clé OpenAI dans les réglages."];
    $prompt = "Pour un produit ou service nommé : '$product_name', propose exactement 7 réponses pertinentes, précises, percutantes et variées à la question suivante pour aider à créer une offre irrésistible : \"$question\". $desc. Réponds STRICTEMENT et UNIQUEMENT en français par un tableau JSON de 7 suggestions courtes et concrètes. N'ajoute aucun commentaire ni texte autour du tableau.";
    $max_attempts = 3;
    $attempt = 0;
    do {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es un expert en copywriting et offres irrésistibles. Réponds STRICTEMENT en français par un tableau JSON de 7 suggestions courtes, concrètes, variées, pertinentes et réalistes, sans aucun texte autour, ni introduction, ni explication. Si tu ne sais pas, invente des exemples crédibles.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 350,
                'temperature' => 0.9,
            ]),
            'timeout' => 30,
        ]);
        if (is_wp_error($response)) return ["Erreur OpenAI."];
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = '';
        if (!empty($body['choices'][0]['message']['content'])) {
            $content = trim($body['choices'][0]['message']['content']);
            error_log('[OIAI][RAW OPENAI] ' . $content);
            // Extraire le premier tableau ou objet JSON du texte
            if (preg_match('/\[.*\]|\{.*\}/s', $content, $matches)) {
                $json = json_decode($matches[0], true);
            } else {
                $json = json_decode($content, true);
            }
            // Aplatir un objet à clé unique (ex: Suggestions_prix) pour obtenir directement le tableau
            if (is_array($json) && count($json) === 1 && !isset($json[0])) {
                $json = array_values($json)[0];
            }
            // Cas spécial : tableau associatif numéroté ou objet imbriqué (clé numérique ou string)
            if (is_array($json) && count($json) >= 1) {
                // 1. Si chaque entrée est un tableau/objet avec plusieurs clés (cas RAW OPENAI #1)
                $all_are_assoc = true;
                $out = [];
                foreach ($json as $k => $v) {
                    if (is_array($v)) {
                        // On concatène toutes les valeurs de chaque sous-objet
                        $out[] = implode(' — ', array_map('trim', array_values($v)));
                    } elseif (is_string($v)) {
                        $out[] = trim($v);
                        $all_are_assoc = false;
                    } else {
                        $all_are_assoc = false;
                    }
                }
                // Si toutes les entrées étaient des objets, on retourne la concaténation
                if ($all_are_assoc && count($out)) {
                    return array_slice($out, 0, 7);
                }
                // Sinon, si ce sont des strings, on retourne le tableau normal
                if (!$all_are_assoc && count($out)) {
                    return array_slice($out, 0, 7);
                }
            }
            // Cas 1 : tableau de chaînes
            if (is_array($json) && isset($json[0]) && is_string($json[0])) {
                // Séparation si suggestions dans une seule chaîne séparée par —
                if (count($json) === 1 && strpos($json[0], '—') !== false) {
                    $parts = array_map('trim', preg_split('/\s*—\s*/', $json[0]));
                    return array_slice($parts, 0, 7);
                }
                return array_slice($json, 0, 7);
            }
            // Cas 2 : tableau d’objets
            if (is_array($json) && isset($json[0]) && is_array($json[0])) {
                $out = [];
                foreach ($json as $obj) {
                    $out[] = implode(' — ', array_map('trim', array_values($obj)));
                }
                return array_slice($out, 0, 7);
            }
            // Cas 3 : tout autre tableau (objet associatif, etc.)
            if (is_array($json)) {
                $flat = array_values($json);
                return array_slice(array_map(function($v) {
                    if (is_array($v)) {
                        // On concatène toutes les valeurs du sous-tableau/objet
                        return implode(' — ', array_map('trim', array_map('strval', array_values($v))));
                    }
                    return trim((string)$v);
                }, $flat), 0, 7);
            }
        }
        $attempt++;
    } while ($attempt < $max_attempts);
    error_log('[OIAI][PARSE FAIL] Réponse non exploitable pour question: ' . $question);
    // Fallback : suggestions par défaut si l'API échoue
    if ($question === 'Qui est le client idéal ?') {
        return [
            'Jeune cadre ambitieux',
            'Mère de famille active',
            'Entrepreneur digital',
            'Étudiant en recherche d’efficacité',
            'Professionnel pressé',
            'Retraité dynamique',
            'Passionné de nouvelles technologies'
        ];
    }
    return ["Suggestion non disponible.","Suggestion non disponible.","Suggestion non disponible.","Suggestion non disponible.","Suggestion non disponible.","Suggestion non disponible.","Suggestion non disponible."];
}

// Audit d'offre IA
function oiai_audit_offer($offer, $tone = 'Neutre') {
    $api_key = get_option('oiai_openai_api_key');
    if (!$api_key) {
        return ["error" => "Clé API OpenAI manquante. Veuillez la renseigner dans les réglages."];
    }
    $prompt = "En tant qu'expert en marketing et création d'offres, analysez l'offre suivante : \"$offer\"\n";
    $prompt .= "Ton d'analyse souhaité : $tone\n";
    $prompt .= "Fournir exactement :\n- 3 points forts (ce qui fonctionne bien)\n- 3 points faibles ou risques potentiels\n- 3 conseils d'amélioration spécifiques et actionnables\nFormat de la réponse : JSON structuré avec les trois catégories.\n\nExemple de format attendu : {\n  \"points_forts\": [\"...\", \"...\", \"...\"],\n  \"points_faibles\": [\"...\", \"...\", \"...\"],\n  \"conseils\": [\"...\", \"...\", \"...\"]\n}";
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => "Tu es un expert en marketing et audit d'offres."],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 600,
            'temperature' => 0.3,
        ]),
        'timeout' => 40,
    ]);
    if (is_wp_error($response)) {
        return ["error" => "Erreur lors de la connexion à OpenAI."];
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    // Extraction du JSON même si le modèle ajoute du texte
    if (preg_match('/\{.*\}/s', $content, $matches)) {
        $json = $matches[0];
        $data = json_decode($json, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return ["error" => "Réponse inattendue de l'API : $content"];
}

// Suggestions de bonus pour bundle IA
function oiai_bundle_bonus_suggestions($product, $price) {
    $api_key = get_option('oiai_openai_api_key');
    if (!$api_key) {
        return ["error" => "Clé API OpenAI manquante. Veuillez la renseigner dans les réglages."];
    }
    $prompt = "Vous êtes un expert en création d'offres commerciales. Pour le produit principal \"$product\" au prix de \"$price DT\", suggérez 5 produits/services bonus complémentaires qui pourraient augmenter la valeur perçue de l'offre. Pour chaque bonus, fournissez :\n- Un titre accrocheur\n- Une courte description (maximum 20 mots)\n- Une valeur estimée (en DT)\nFormat de réponse : JSON avec 5 objets bonus (titre, description, valeur).\n\nExemple : [{\"titre\":\"...\",\"description\":\"...\",\"valeur\":\"...\"}, ...]";
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => "Tu es un expert en création d'offres commerciales."],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 700,
            'temperature' => 0.7,
        ]),
        'timeout' => 40,
    ]);
    if (is_wp_error($response)) {
        return ["error" => "Erreur lors de la connexion à OpenAI."];
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    // Extraction du JSON même si le modèle ajoute du texte
    if (preg_match('/\[.*\]/s', $content, $matches)) {
        $json = $matches[0];
        $data = json_decode($json, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return ["error" => "Réponse inattendue de l'API : $content"];
}

// Génération du texte marketing du bundle IA
function oiai_bundle_marketing_text($product, $price, $type, $bonuses, $prix_normal, $prix_final) {
    $api_key = get_option('oiai_openai_api_key');
    if (!$api_key) {
        return "Clé API OpenAI manquante. Veuillez la renseigner dans les réglages.";
    }
    $bonus_str = '';
    if (is_array($bonuses)) {
        $bonus_str = implode(', ', array_map(function($b) { return $b['titre'] ?? ''; }, $bonuses));
    }
    $prompt = "Créez un texte marketing attrayant pour le bundle \"$product\" à \"$price DT\" avec les bonus sélectionnés suivants : $bonus_str.\n🎁 Offre spéciale : [Nom du bundle] Obtenez [produit principal] + [bonus 1, bonus 2, etc.] pour seulement [$prix_final DT] au lieu de [$prix_normal DT].\n⏳ Offre limitée : $type\n📍 Profitez-en maintenant !";
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => "Tu es un expert en copywriting marketing de bundles."],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 400,
            'temperature' => 0.5,
        ]),
        'timeout' => 40,
    ]);
    if (is_wp_error($response)) {
        return "Erreur lors de la connexion à OpenAI.";
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    return $content;
}

// Générateur d'offre IA (mixte Facebook & site web)
function oiai_generate_offer_ia($product, $price, $type, $answers, $bonuses, $prix_normal, $prix_final) {
    $api_key = get_option('oiai_openai_api_key');
    if (!$api_key) {
        return ["error" => "Clé API OpenAI manquante. Veuillez la renseigner dans les réglages."];
    }
    // Construction du contexte pour l'offre
    $answers_str = '';
    if (is_array($answers)) {
        foreach ($answers as $i => $ans) {
            $answers_str .= "Q".($i+1).": $ans\n";
        }
    }
    $bonus_str = '';
    if (is_array($bonuses)) {
        $bonus_str = implode(', ', array_map(function($b) { return $b['titre'] ?? ''; }, $bonuses));
    }
    $bonus_desc = '';
    if (is_array($bonuses)) {
        foreach ($bonuses as $b) {
            $bonus_desc .= ($b['titre'] ?? '') . " : " . ($b['description'] ?? '') . " (valeur : " . ($b['valeur'] ?? '') . " DT)\n";
        }
    }
    // Prompt Facebook (court, emojis, ORGANISÉ et lisible)
    $prompt_fb = "En tant qu'expert en copywriting, crée une offre irrésistible pour Facebook (maximum 80 mots, avec beaucoup d'emojis, ton accrocheur et dynamique).\nProduit : $product\nPrix : $price DT\nType d'offre : $type\n$answers_str\nBonus inclus : $bonus_str\nValeur totale normale : $prix_normal DT, Prix final : $prix_final DT.\n\nFormat :\n- Texte organisé, aéré, chaque ligne contient 1 ou 2 phrases courtes et cohérentes.\n- Utilise des retours à la ligne pour séparer les blocs (accroche, bénéfices, bonus, garantie, urgence, call-to-action).\n- Mets les mots importants en gras avec **deux astérisques** (ex : **Livraison offerte**).\n- Utilise des emojis adaptés pour chaque bloc.\n- Texte prêt à publier pour Facebook, très attractif.\n";
    // Prompt site web (détaillé, conversion)
    $prompt_web = "En tant qu'expert en copywriting, rédige une page d'offre irrésistible optimisée pour la conversion sur un site web.\nProduit : $product\nPrix : $price DT\nType d'offre : $type\n$answers_str\nBonus détaillés :\n$bonus_desc\nValeur totale normale : $prix_normal DT, Prix final : $prix_final DT.\nFormat : texte structuré (titre, accroche, bénéfices, bonus, urgence, call-to-action), sans emojis, prêt à intégrer sur une page web. Mets les mots importants en gras avec **deux astérisques** (ex : **Livraison offerte**). Chaque ligne ou paragraphe doit être lisible et aéré.";
    // Appel API pour Facebook
    $resp_fb = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => "Tu es un expert en copywriting d'offres Facebook."],
                ['role' => 'user', 'content' => $prompt_fb],
            ],
            'max_tokens' => 400,
            'temperature' => 0.65,
        ]),
        'timeout' => 40,
    ]);
    // Appel API pour site web
    $resp_web = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => "Tu es un expert en copywriting d'offres web."],
                ['role' => 'user', 'content' => $prompt_web],
            ],
            'max_tokens' => 650,
            'temperature' => 0.5,
        ]),
        'timeout' => 40,
    ]);
    $fb = $web = '';
    if (!is_wp_error($resp_fb)) {
        $body = json_decode(wp_remote_retrieve_body($resp_fb), true);
        $fb = $body['choices'][0]['message']['content'] ?? '';
    }
    if (!is_wp_error($resp_web)) {
        $body = json_decode(wp_remote_retrieve_body($resp_web), true);
        $web = $body['choices'][0]['message']['content'] ?? '';
    }
    return [
        'facebook' => $fb,
        'siteweb' => $web
    ];
}
