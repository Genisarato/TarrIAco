@extends('layouts.app')

@section('page-title', 'Anàlisi de Pacient')

@section('content')
    <div class="content-card">
        {{-- CAPÇALERA --}}
        <div class="card-header-row">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <h1 class="card-title">Anàlisi de Pacient</h1>
        </div>
        <p class="card-subtitle">Introdueix l'ID del pacient per obtenir l'anàlisi predictiva jeràrquica</p>

        {{-- ERRORS --}}
        @if ($errors->has('api'))
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#991b1b; font-weight:500;">
                <svg style="width:16px; height:16px; display:inline; margin-right:4px; vertical-align:text-bottom;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                {{ $errors->first('api') }}
            </div>
        @endif

        {{-- FORMULARI --}}
        <form method="POST" action="{{ route('analyze') }}" id="analysis-form" style="display:flex; gap:12px; align-items: flex-end; margin-bottom: 30px;">
            @csrf
            <div style="flex:1;">
                <label class="form-label" for="dni">ID Pacient (Test Set)</label>
                <input
                    type="text"
                    class="form-input"
                    id="dni"
                    name="dni"
                    placeholder="Ex: 24954, 22644, 1024..."
                    value="{{ old('dni', $dni ?? '') }}"
                    required
                    autofocus
                    style="margin-bottom:0;"
                >
            </div>
            <button type="submit" class="btn-primary" id="btn-analyze" style="height:42px; padding: 0 24px;">Analitzar</button>
        </form>

        {{-- RESULTATS --}}
        @if (isset($resultat))
            <div class="results-container" style="animation: fadeInUp 0.5s ease-out;">
                <div style="display:flex; align-items:center; justify-content:between; margin-bottom:20px;">
                    <h2 style="font-size:20px; font-weight:800; color:#0f172a; margin:0;">Resultats de l'Anàlisi</h2>
                    <span style="margin-left:auto; padding:4px 12px; background:#e2e8f0; border-radius:20px; font-size:12px; font-weight:600; color:#475569;">Pacient #{{ $resultat['pacient']['id_pacient'] }}</span>
                </div>

                {{-- GRAELLA DE TARGETES (3 COLUMNE) --}}
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-bottom:24px;">
                    
                    {{-- 1. TARGETA PERFIL --}}
                    <div style="padding:20px; background:white; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                            <div style="width:36px; height:36px; background:#eff6ff; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#2563eb;">
                                <svg style="width:20px; height:20px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                            <h3 style="font-size:15px; font-weight:700; color:#1e293b; margin:0;">Perfil del Pacient</h3>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div>
                                <p style="font-size:11px; color:#64748b; margin:0; text-transform: uppercase; font-weight:700;">Edat / Sexe</p>
                                <p style="font-size:14px; color:#1e293b; font-weight:600; margin:4px 0 0 0;">{{ $resultat['pacient']['grup_edat'] }} anys / {{ $resultat['pacient']['sexe'] == 'H' ? 'Masculí' : 'Femenina' }}</p>
                            </div>
                            <div>
                                <p style="font-size:11px; color:#64748b; margin:0; text-transform: uppercase; font-weight:700;">Estat Actual</p>
                                <span style="display:inline-block; margin-top:4px; padding:2px 8px; border-radius:6px; font-size:12px; font-weight:700; background: {{ $resultat['pacient']['cronic_actual'] == 'NO' ? '#f1f5f9' : ($resultat['pacient']['cronic_actual'] == 'PCC' ? '#fef3c7' : '#fee2e2') }}; color: {{ $resultat['pacient']['cronic_actual'] == 'NO' ? '#475569' : ($resultat['pacient']['cronic_actual'] == 'PCC' ? '#92400e' : '#991b1b') }};">
                                    {{ $resultat['pacient']['cronic_actual'] }}
                                </span>
                            </div>
                            <div>
                                <p style="font-size:11px; color:#64748b; margin:0; text-transform: uppercase; font-weight:700;">Diagnòstics</p>
                                <p style="font-size:14px; color:#1e293b; font-weight:600; margin:4px 0 0 0;">{{ $resultat['pacient']['diags_totals'] }} totals</p>
                            </div>
                            <div>
                                <p style="font-size:11px; color:#64748b; margin:0; text-transform: uppercase; font-weight:700;">Fàrmacs</p>
                                <p style="font-size:14px; color:#1e293b; font-weight:600; margin:4px 0 0 0;">{{ $resultat['pacient']['farmacs_totals'] }} actius</p>
                            </div>
                        </div>
                    </div>

                    {{-- 2. TARGETA SIMILITUD (FAISS) --}}
                    <div style="padding:20px; background:white; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                            <div style="width:36px; height:36px; background:#f0fdf4; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#16a34a;">
                                <svg style="width:20px; height:20px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <h3 style="font-size:15px; font-weight:700; color:#1e293b; margin:0;">Casos Similars (FAISS)</h3>
                        </div>
                        <div style="margin-bottom:12px;">
                            <p style="font-size:12px; color:#475569; margin-bottom:8px;">Distribució de {{ $resultat['pacient']['n_veins'] }} veïns reals:</p>
                            <div style="display:flex; height:8px; border-radius:4px; overflow:hidden; background:#e2e8f0;">
                                <div style="width:{{ $resultat['pacient']['pct_pcc'] }}%; background:#f59e0b;" title="PCC"></div>
                                <div style="width:{{ $resultat['pacient']['pct_maca'] }}%; background:#ef4444;" title="MACA"></div>
                                <div style="width:{{ $resultat['pacient']['pct_no'] }}%; background:#94a3b8;" title="NO"></div>
                            </div>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:600;">
                            <span style="color:#b45309;">PCC: {{ $resultat['pacient']['pct_pcc'] }}%</span>
                            <span style="color:#b91c1c;">MACA: {{ $resultat['pacient']['pct_maca'] }}%</span>
                            <span style="color:#475569;">NO: {{ $resultat['pacient']['pct_no'] }}%</span>
                        </div>
                    </div>

                    {{-- 3. TARGETA PREDICCIÓ (ML V3) --}}
                    @if(isset($resultat['prediccio_v3']))
                        <div style="padding:20px; background:white; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                                <div style="width:36px; height:36px; background:#f5f3ff; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#7c3aed;">
                                    <svg style="width:20px; height:20px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                                </div>
                                <h3 style="font-size:15px; font-weight:700; color:#1e293b; margin:0;">Model Predictiu (ML V3)</h3>
                            </div>
                            <div style="text-align:center; padding:10px 0;">
                                <p style="font-size:11px; color:#64748b; margin:0; text-transform: uppercase; font-weight:700;">Suggereix Classificar com</p>
                                @php
                                    $pred = $resultat['prediccio_v3']['resultat'];
                                    $color = $pred == 'NO' ? '#64748b' : ($pred == 'PCC' ? '#f59e0b' : '#ef4444');
                                    $conf = $resultat['prediccio_v3']['confianca'] * 100;
                                @endphp
                                <p style="font-size:32px; font-weight:900; color: {{ $color }}; margin:5px 0;">{{ $pred }}</p>
                                <div style="margin-top:12px;">
                                    <p style="font-size:11px; color:#64748b; margin-bottom:4px; font-weight:700;">CONFIANÇA: {{ number_format($conf, 1) }}%</p>
                                    <div style="height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden;">
                                        <div style="width:{{ $conf }}%; background:{{ $color }}; height:100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- INFORME GENERATIU --}}
                @if (isset($resultat['informe']))
                    <div style="padding:24px; background:linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius:20px; border:1px solid #bae6fd; position:relative; overflow:hidden;">
                        <div style="position:absolute; top:-20px; right:-20px; opacity:0.1; color:#0369a1;">
                            <svg style="width:120px; height:120px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        </div>
                        
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                            <div style="width:40px; height:40px; background:#0369a1; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; box-shadow: 0 4px 6px -1px rgb(3 105 161 / 0.3);">
                                <svg style="width:22px; height:22px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10H12V2z"></path><path d="M12 2a10 10 0 0 1 10 10h-10V2z"></path><path d="M12 12L2.8 7.3"></path><path d="M12 12l9.2 4.7"></path></svg>
                            </div>
                            <div>
                                <h3 style="font-size:16px; font-weight:800; color:#0369a1; margin:0;">Informe de Decisió Clínica (IA)</h3>
                                <p style="font-size:11px; color:#0ea5e9; font-weight:600; margin:0; text-transform:uppercase; letter-spacing:0.5px;">Generat amb DeepSeek-R1:8b via Ollama</p>
                            </div>
                        </div>

                        <div style="background:rgba(255,255,255,0.6); padding:20px; border-radius:14px; border:1px solid rgba(255,255,255,0.8); font-size:15px; color:#0c4a6e; line-height:1.7; white-space:pre-wrap; font-family: inherit;">{{ str_replace(['**', '#'], '', $resultat['informe']) }}</div>
                    </div>
                @endif

                {{-- JSON DEBUG --}}
                <details style="margin-top:24px; border-top:1px solid #f1f5f9; padding-top:16px;">
                    <summary style="font-size:12px; color:#94a3b8; cursor:pointer; font-weight:600;">Detalls tècnics del motor de decisió (JSON)</summary>
                    <pre style="font-size:11px; color:#64748b; background:#f8fafc; padding:16px; border-radius:12px; margin-top:12px; overflow-x:auto; border:1px solid #f1f5f9;">{{ json_encode($resultat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            </div>
        @endif
    </div>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
    </style>
@endsection