@extends('layouts.app')

@section('title', 'Confirmaciones')

@section('content')
<h4 class="fw-bold mb-3" style="color:#1B4F8A;">
    <i class="fas fa-check-double me-2"></i>Estado de Entrega
</h4>

<div class="card card-mp mb-4">
    <div class="card-body">
        <div class="row align-items-end g-2">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Seleccionar mensaje</label>
                <select id="sel-mensaje" class="form-select">
                    <option value="">— Elige un mensaje —</option>
                    @foreach($mensajes as $m)
                    <option value="{{ $m->id }}">
                        {{ $m->created_at?->format('d/m/Y H:i') }} — {{ Str::limit($m->titulo, 60) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button id="btn-ver" class="btn btn-mp w-100">
                    <i class="fas fa-search me-1"></i>Ver confirmaciones
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Stats --}}
<div id="stats-sec" class="d-none mb-4">
    <div class="row g-3">
        <div class="col-sm-4">
            <div class="card card-mp text-center py-3">
                <div class="display-6 fw-bold" id="stat-total" style="color:#1B4F8A;">-</div>
                <small class="text-muted">PCs objetivo</small>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card card-mp text-center py-3">
                <div class="display-6 fw-bold text-success" id="stat-recibidos">-</div>
                <small class="text-muted">Recibieron (<span id="stat-pct-r">0</span>%)</small>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card card-mp text-center py-3">
                <div class="display-6 fw-bold text-info" id="stat-descargas">-</div>
                <small class="text-muted">Descargaron (<span id="stat-pct-d">0</span>%)</small>
            </div>
        </div>
    </div>
</div>

{{-- Tabla confirmaciones --}}
<div id="tabla-sec" class="d-none">
    <div class="card card-mp">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:#F0F4F8;">
                        <tr>
                            <th class="ps-3">PC</th>
                            <th>IP</th>
                            <th class="text-center">Recibido</th>
                            <th class="text-center">Visto</th>
                            <th class="text-center">Descargado</th>
                        </tr>
                    </thead>
                    <tbody id="conf-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="sin-datos" class="text-center text-muted py-5 d-none">
    <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
    Sin confirmaciones registradas para este mensaje.
</div>
@endsection

@push('scripts')
<script>
const check = ok => ok
    ? '<i class="fas fa-check-circle text-success fs-5"></i>'
    : '<i class="fas fa-times-circle text-danger opacity-25 fs-5"></i>';

document.getElementById('btn-ver').addEventListener('click', async function() {
    const id = document.getElementById('sel-mensaje').value;
    if (!id) return;

    const r = await fetch(`{{ route('confirmaciones.data') }}?mensaje_id=${id}`);
    const d = await r.json();

    // Stats
    const s = d.stats;
    document.getElementById('stat-total').textContent    = s.total;
    document.getElementById('stat-recibidos').textContent = s.recibidos;
    document.getElementById('stat-pct-r').textContent    = s.pct_recibido;
    document.getElementById('stat-descargas').textContent = s.descargas;
    document.getElementById('stat-pct-d').textContent    = s.pct_descarga;
    document.getElementById('stats-sec').classList.remove('d-none');

    const tbody = document.getElementById('conf-body');
    const sinDatos = document.getElementById('sin-datos');
    const tablaSec = document.getElementById('tabla-sec');

    if (!d.confirmaciones.length) {
        tablaSec.classList.add('d-none');
        sinDatos.classList.remove('d-none');
        return;
    }

    sinDatos.classList.add('d-none');
    tablaSec.classList.remove('d-none');

    tbody.innerHTML = d.confirmaciones.map(c => `
        <tr>
            <td class="ps-3 fw-semibold">${c.pc_nombre}</td>
            <td class="text-muted small">${c.pc_ip}</td>
            <td class="text-center">${check(c.recibido)}</td>
            <td class="text-center">${check(c.visto)}</td>
            <td class="text-center">${check(c.descargado)}</td>
        </tr>
    `).join('');
});
</script>
@endpush
