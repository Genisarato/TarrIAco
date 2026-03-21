<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIA - El Meu Espai de Salut</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f6fa; color: #334155; margin: 0; padding: 0; }
        .header { background: #ffffff; padding: 16px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .header-title { font-size: 18px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .container { max-width: 600px; margin: 24px auto; padding: 0 16px; animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        
        .welcome-text { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: -0.3px; }
        .subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; line-height: 1.4; }
        
        .card { background: #ffffff; border-radius: 20px; padding: 24px; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04); margin-bottom: 20px; }
        
        /* Widgets principals (Gauges) */
        .gauges-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .gauge-card { background: #ffffff; border-radius: 20px; padding: 20px 16px; text-align: center; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04); position: relative; }
        
        .gauge-title { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 16px; }
        .gauge-subtitle { font-size: 11px; color: #94a3b8; display: block; margin-top: 2px; }
        
        .svg-gauge { width: 100%; max-width: 140px; margin: 0 auto; display: block; overflow: visible; }
        .gauge-bg { fill: none; stroke: #f1f5f9; stroke-width: 12; stroke-linecap: round; }
        .gauge-val-yellow { fill: none; stroke: #eab308; stroke-width: 12; stroke-linecap: round; stroke-dasharray: 125.66; transition: stroke-dashoffset 1.5s ease; }
        .gauge-val-green { fill: none; stroke: #10b981; stroke-width: 12; stroke-linecap: round; stroke-dasharray: 125.66; transition: stroke-dashoffset 1.5s ease; }
        .gauge-val-orange { fill: none; stroke: #f97316; stroke-width: 12; stroke-linecap: round; stroke-dasharray: 125.66; transition: stroke-dashoffset 1.5s ease; }
        
        .gauge-center { position: absolute; top: 65%; left: 50%; transform: translate(-50%, -50%); text-align: center; width: 100%; }
        .gauge-big-number { font-size: 32px; font-weight: 800; color: #1e293b; line-height: 1; margin-bottom: 4px; }
        .gauge-label { font-size: 12px; font-weight: 500; font-family: 'Inter', sans-serif;}
        
        .status-yellow { color: #ca8a04; }
        .status-green { color: #059669; }
        .status-orange { color: #ea580c; }
        
        /* Linear widget */
        .linear-widget { margin-top: 8px; }
        .linear-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
        .linear-value { font-size: 32px; font-weight: 800; color: #1e293b; line-height: 1; }
        .linear-label { font-size: 12px; font-weight: 500; color: #475569; display: block; margin-top: 4px; }
        .linear-track { height: 16px; background: #f1f5f9; border-radius: 8px; position: relative; overflow: hidden; }
        .linear-fill { height: 100%; border-radius: 8px; position: absolute; left: 0; top: 0; transition: width 1.5s ease; }
        .linear-marker { position: absolute; width: 4px; height: 24px; background: #1e293b; top: -4px; border-radius: 2px; z-index: 2; transition: left 1.5s ease; margin-left: -2px;}
        .marker-label { position: absolute; font-size: 10px; font-weight: 700; color: #1e293b; top: -20px; transform: translateX(-50%); transition: left 1.5s ease; }
        
        .ai-advice-card { background: linear-gradient(135deg, #ffffff 0%, #fefce8 100%); border: 1px solid #fef08a; border-left: 4px solid #eab308; }
        .ai-title { font-size: 16px; font-weight: 700; color: #854d0e; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .ai-text { font-size: 14.5px; line-height: 1.6; color: #422006; font-weight: 500; }
        
        .btn-logout { background: transparent; color: #64748b; border: none; font-size: 14px; font-weight: 600; cursor: pointer; }

        @media (max-width: 640px) {
            .gauges-row { grid-template-columns: 1fr; gap: 12px; }
            .header { padding: 12px 16px; }
            .welcome-text { font-size: 20px; }
            .card { padding: 20px; }
            .gauge-card { padding: 16px; }
            .linear-value { font-size: 28px; }
            .gauge-big-number { font-size: 28px; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
            </svg>
            SIA Health
        </div>
        <form method="POST" action="{{ route('pacient.logout') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="btn-logout">Surt</button>
        </form>
    </header>

    <div class="container">
        @if($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px;">
                {{ $errors->first() }}
            </div>
        @endif

        @if(isset($resultat))
            @php 
                $pacient = $resultat['pacient']; 
                $isDeceased = (isset($pacient['situacio']) && strtoupper(trim($pacient['situacio'])) === 'D');
            @endphp
            
            @if($isDeceased)
                <h1 class="welcome-text">El teu resum de salut</h1>
                <p class="subtitle">Expedient tancat</p>
                <div class="card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px 24px; text-align: center; margin-bottom: 24px;">
                    <h2 style="color: #475569; font-size: 20px; margin: 0 0 12px 0; font-weight: 700;">Dades no disponibles</h2>
                    <p style="color: #64748b; font-size: 15px; margin: 0; line-height: 1.6;">
                        En aquests moments no es pot mostrar l'estat del seguiment.
                    </p>
                </div>
            @else
                @php     
                    $consells = $resultat['consells'];
                
                // Càlculs per a la interfície
                $diags = $pacient['diags_totals'];
                $farmacs = $pacient['farmacs_totals'];
                $urgencies = $pacient['urg_totals'] ?? 0;
                $ingressos = $pacient['hosp_totals'] ?? 0;
                
                // Mètrica de "Benestar" (0-100) seguint la fórmula 
                // Puntuacion = 100 - (W_1 * Urgencias) - (W_2 * Ingresos) - (W_3 * Farmacos) - (W_4* Carga_Cronica)
                $w1 = 4;   // Urgències
                $w2 = 8;   // Ingressos hospitalaris
                $w3 = 1.5; // Fàrmacs
                $w4 = 3;   // Càrrega Crònica / Diagnòstics
                
                $salut = 100 - ($w1 * $urgencies) - ($w2 * $ingressos) - ($w3 * $farmacs) - ($w4 * $diags);
                $salut = max(0, min(100, $salut)); // Limitem a max 100 i min 0

                // Càlcul per a la mitjana de salut del grup
                $mg_urg = $pacient['mitjana_grup_urg'] ?? 0;
                $mg_hosp = $pacient['mitjana_grup_hosp'] ?? 0;
                $mg_farmacs = $pacient['mitjana_grup_farmacs'] ?? 0;
                $mg_diags = $pacient['mitjana_grup_diags'] ?? 0;
                
                $salutGrup = 100 - ($w1 * $mg_urg) - ($w2 * $mg_hosp) - ($w3 * $mg_farmacs) - ($w4 * $mg_diags);
                $salutGrup = max(0, min(100, $salutGrup));
                
                // Avaluació Relativa al Grup (totes les estadístiques segons els similars)
                $diff = $salut - $salutGrup;
                if ($diff >= 5) {
                    $salutColorClass = 'green';
                    $salutText = 'Millor que el grup';
                } elseif ($diff >= -5) {
                    $salutColorClass = 'yellow';
                    $salutText = 'En la mitjana';
                } else {
                    $salutColorClass = 'orange';
                    $salutText = 'Atenció requerida';
                }

                // Escales Dinàmiques per als gràfics inferiors
                $maxDiags = max(15, ($mg_diags * 2));
                $diagsPct = min(100, ($diags / max(1, $maxDiags)) * 100);
                $diagsOffset = 125.66 * (1 - ($diagsPct / 100));
                
                $diagsDiff = $diags - $mg_diags;
                $diagsColor = ($diagsDiff <= 0.5) ? 'green' : (($diagsDiff <= 2) ? 'yellow' : 'orange');
                $diagsText = ($diagsDiff <= 0.5) ? 'Bé' : (($diagsDiff <= 2) ? 'Lleu' : 'Vigilància');

                $maxFarmacs = max(20, ($mg_farmacs * 2));
                $farmacsPct = min(100, ($farmacs / max(1, $maxFarmacs)) * 100);
                $farmacsOffset = 125.66 * (1 - ($farmacsPct / 100));
                
                $farmacsDiff = $farmacs - $mg_farmacs;
                $farmacsColor = ($farmacsDiff <= 1) ? 'green' : (($farmacsDiff <= 3) ? 'yellow' : 'orange');
                $farmacsText = ($farmacsDiff <= 1) ? 'Bé' : (($farmacsDiff <= 3) ? 'Moderats' : 'Elevats');

            @endphp            <h1 class="welcome-text">El teu resum de salut</h1>
            <p class="subtitle">Actualitzat avui • Compartint camí amb persones de la teva edat ({{ $pacient['grup_edat'] }} anys)</p>

            <!-- Small Win / Banner de suport -->
            @if($diff >= -3)
            <div class="card" style="background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 24px; box-shadow: 0 4px 15px -3px rgba(16, 185, 129, 0.1);">
                <div style="font-size: 28px; margin-bottom: 12px;">🌱</div>
                <h2 style="color: #065f46; font-size: 20px; margin: 0 0 8px 0; font-weight: 800;">Bon treball!</h2>
                <p style="color: #047857; font-size: 15px; margin: 0; font-weight: 500; line-height: 1.5;">
                    Ho estàs fent molt bé. Continua gestionant la teva salut així de bé per mantenir el teu benestar!
                </p>
            </div>
            @else
            <div class="card" style="background: #fffbeb; border: 1px solid #fde047; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 24px; box-shadow: 0 4px 15px -3px rgba(250, 204, 21, 0.1);">
                <div style="font-size: 28px; margin-bottom: 12px;">👀</div>
                <h2 style="color: #854d0e; font-size: 20px; margin: 0 0 8px 0; font-weight: 800;">Una petita atenció</h2>
                <p style="color: #a16207; font-size: 15px; margin: 0; font-weight: 500; line-height: 1.5;">
                    Hauries de vigilar una mica més de prop els teus hàbits juntament amb el teu equip mèdic.
                </p>
            </div>
            @endif

            <!-- Gràfic Principal de Salut (Estat del teu seguiment) -->
            <div class="card" style="margin-bottom: 24px; padding: 30px;">
                <div style="margin-bottom: 30px; text-align: center;">
                    <h2 style="font-size: 15px; color: #64748b; margin: 0 0 8px 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Estat del teu seguiment</h2>
                    @php
                        if ($salut < 50) $seguimentMetaphor = "Anar amb cura";
                        else $seguimentMetaphor = "Estar bé";
                    @endphp
                    <div style="font-size: 28px; font-weight: 800; color: #1e293b;">
                        Avui la indicació és <span style="color: #6366f1;">{{ strtolower($seguimentMetaphor) }}</span>
                    </div>
                </div>
                
                <div style="padding-top: 50px; padding-bottom: 20px; position:relative;">
                    <div class="linear-track" style="height: 24px; border-radius: 12px; background: #f8fafc; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                        <div class="linear-fill" style="width: {{ $salut }}%; background: linear-gradient(90deg, #c4b5fd 0%, #bae6fd 50%, #a7f3d0 100%); border-radius: 12px; transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                    </div>
                    
                    <div class="marker-label" style="left: {{ $salutGrup }}%; top: -30px; color: #94a3b8; font-size: 12px; transform: translateX(-50%); white-space: nowrap; font-weight: 600;">Mitjana
                        <svg width="8" height="6" viewBox="0 0 10 6" style="display:block; margin:2px auto 0; fill: #cbd5e1;">
                            <polygon points="0,0 10,0 5,6"></polygon>
                        </svg>
                    </div>
                    <div class="linear-marker" style="left: {{ $salutGrup }}%; height: 26px; top: -2px; width: 3px; background: #cbd5e1; border-radius: 1.5px;"></div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 12px; font-size: 13px; color: #94a3b8; font-weight: 600;">
                        <span>Anar amb cura</span>
                        <span>Estar bé</span>
                    </div>
                </div>
            </div>

            <!-- Gràfics complementaris (Patologies i Fàrmacs) -->
            <div class="gauges-row">
                <!-- Temes que estem cuidant -->
                <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 24px; margin-bottom: 0;">
                    <div style="background: #fce7f3; padding: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#db2777" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 600; color: #64748b; margin-bottom: 2px;">Temes que estem cuidant</div>
                        <div style="font-size: 32px; font-weight: 800; color: #1e293b; line-height: 1;">
                            {{ $diags }} <span style="font-size: 14px; font-weight: 600; color: #94a3b8; vertical-align: middle;"> patologies</span>
                        </div>
                    </div>
                </div>

                <!-- El teu pla de suport -->
                <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 24px; margin-bottom: 0;">
                    <div style="background: #e0f2fe; padding: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="10" rx="5" ry="5"></rect>
                            <line x1="12" y1="7" x2="12" y2="17"></line>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 600; color: #64748b; margin-bottom: 2px;">El teu pla de suport</div>
                        <div style="font-size: 32px; font-weight: 800; color: #1e293b; line-height: 1;">
                            {{ $farmacs }} <span style="font-size: 14px; font-weight: 600; color: #94a3b8; vertical-align: middle;"> medicaments</span>
                        </div>
                    </div>
                </div>
            </div>



            <div class="card ai-advice-card">
                <div class="ai-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    El teu objectiu d'avui
                </div>
                <div class="ai-text">
                    {{ $consells }}
                </div>
            </div>
            @endif
            
            <p style="text-align: center; color: #cbd5e1; font-size: 12px; margin-top: 30px; margin-bottom: 40px; font-weight: 500;">
                SIA Health · Hackathon 2026
            </p>
        @else
            <div class="card">
                <p>No s'ha pogut carregar l'informe. Torna a intentar-ho.</p>
            </div>
        @endif
    </div>
</body>
</html>
