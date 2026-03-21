@extends('layouts.app')

@section('page-title', 'Classificació de Rols')

@section('content')
    <div class="content-card">
        <div class="card-header-row">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 17 12 22 22 17"></polyline>
                <polyline points="2 12 12 17 22 12"></polyline>
            </svg>
            <h1 class="card-title">Classificació de Rols</h1>
        </div>
        <p class="card-subtitle">Selecciona un grup per obtenir l'usuari promig</p>

        {{-- Error de l'API --}}
        @if (isset($error))
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#991b1b; font-weight:500;">
                ⚠️ {{ $error }}
            </div>
        @endif

        <form method="GET" action="{{ route('classificacio') }}" id="classificacio-form">
            <label class="form-label" for="grup">Grup</label>
            <select class="form-select" id="grup" name="grup">
                <option value="" disabled {{ $grup ? '' : 'selected' }}>Selecciona un grup...</option>
                <option value="1" {{ $grup == '1' ? 'selected' : '' }}>Grup 1</option>
                <option value="2" {{ $grup == '2' ? 'selected' : '' }}>Grup 2</option>
                <option value="3" {{ $grup == '3' ? 'selected' : '' }}>Grup 3</option>
            </select>

            <button type="submit" class="btn-primary" id="btn-classificacio">Consultar</button>
        </form>

        {{-- Resultats de l'API --}}
        @if (isset($resultat))
            <div style="margin-top:24px; padding:20px; background:#f0f6ff; border-radius:14px; border:1px solid #dbeafe;">
                <p style="font-size:13px; font-weight:600; color:#3b6fcc; margin-bottom:8px;">Resultat — Grup {{ $grup }}</p>
                <pre style="font-size:13px; color:#334155; white-space:pre-wrap; word-break:break-word; margin:0;">{{ is_array($resultat) ? json_encode($resultat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $resultat }}</pre>
            </div>
        @endif
    </div>
@endsection
