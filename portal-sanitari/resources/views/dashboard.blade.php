@extends('layouts.app')

@section('page-title', 'Anàlisi de Pacient')

@section('content')
    <div class="content-card">
        <div class="card-header-row">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <h1 class="card-title">Anàlisi de Pacient</h1>
        </div>
        <p class="card-subtitle">Introdueix el DNI del pacient per obtenir l'anàlisi predictiva</p>

        {{-- Errors de l'API --}}
        @if ($errors->has('api'))
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#991b1b; font-weight:500;">
                ⚠️ {{ $errors->first('api') }}
            </div>
        @endif

        <form method="POST" action="{{ route('analyze') }}" id="analysis-form">
            @csrf
            <label class="form-label" for="dni">DNI / NIE</label>
            <input
                type="text"
                class="form-input"
                id="dni"
                name="dni"
                placeholder="Ex: 12345678A"
                value="{{ old('dni', $dni ?? '') }}"
                required
                autofocus
            >
            <button type="submit" class="btn-primary" id="btn-analyze">Analitzar</button>
        </form>

        {{-- Resultats de l'API --}}
        @if (isset($resultat))
            <div style="margin-top:24px; padding:20px; background:#f0f6ff; border-radius:14px; border:1px solid #dbeafe;">
                <p style="font-size:13px; font-weight:600; color:#3b6fcc; margin-bottom:8px;">Resultat — Pacient {{ $dni }}</p>
                <pre style="font-size:13px; color:#334155; white-space:pre-wrap; word-break:break-word; margin:0;">{{ is_array($resultat) ? json_encode($resultat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $resultat }}</pre>
            </div>
        @endif
    </div>
@endsection
