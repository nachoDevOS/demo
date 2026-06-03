@extends('layouts.app')

@section('title', 'Historial')

@push('styles')
<style>
    .badge-tipo { font-size: .75rem; padding: .35em .7em; }
    .tabla-historial tbody tr { cursor: pointer; }
    .tabla-historial tbody tr:hover { background: #EEF4FF; }
    .filtros-bar { background: #fff; border-radius: 10px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
</style>
@endpush

@section('content')
<h4 class="fw-bold mb-3" style="color:#1B4F8A;">
    <i class="fas fa-history me-2"></i>Historial de Mensajes
</h4>

{{-- Filtros --}}
<div class="filtros-bar d-flex flex-wrap gap-2 align-items-end">
    <div>
        <label class="form-label small fw-semibold mb-1">Tipo</label>
        <select id="filtro-tipo" class="form-select form-select-sm" style="min-width:140px;">
            <option value="">Todos</option>
            <option value="notificacion">Notificación</option>
            <option value="instructivo">Instructivo</option>
            <option value="urgente">Urgente</option>
            <option value="reunion">Reunión</option>
        </select>
    </div>
    <div>
        <label class="form-label small fw-semibold mb-1">Desde</label>
        <input type="date" id="filtro-desde" class="form-control form-control-sm">
    </div>
    <div>
        <label class="form-label small fw-semibold mb-1">Hasta</label>
        <input type="date" id="filtro-hasta" class="form-control form-control-sm">
    </div>
    <button id="btn-filtrar" class="btn btn-mp btn-sm mt-1">
        <i class="fas fa-search me-1"></i>Filtrar
    </button>
    <button id="btn-limpiar" class="btn btn-outline-secondary btn-sm mt-1">Limpiar</button>
</div>

{{-- Tabla --}}
<div class="card card-mp">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 tabla-historial">
                <thead style="background:#F0F4F8;">
                    <tr>
                        <th class="ps-3">Fecha / Hora</th>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>Remitente</th>
                        <th>Archivo</th>
                        <th>PCs</th>
                    </tr>
                </thead>
                <tbody id="tabla-body">
                    <tr><td colspan="6" class="text-center py-4 text-muted">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="paginacion" class="d-flex justify-content-center p-3 gap-1"></div>
    </div>
</div>

{{-- Modal detalle --}}
<div class="modal fade" id="modal-detalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="modal-header" style="background:#1B4F8A; color:#fff;">
                <h5 class="modal-title" id="modal-titulo">Detalle del Mensaje</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted small fw-semibold">Tipo</div>
                    <div class="col-sm-8" id="d-tipo"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted small fw-semibold">Remitente</div>
                    <div class="col-sm-8" id="d-remitente"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted small fw-semibold">Fecha y hora</div>
                    <div class="col-sm-8" id="d-fecha"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted small fw-semibold">PCs objetivo</div>
                    <div class="col-sm-8" id="d-pcs"></div>
                </div>
                <hr>
                <h6 class="fw-bold">Mensaje:</h6>
                <p id="d-cuerpo" class="bg-light p-3 rounded" style="white-space:pre-wrap;"></p>
                <div id="d-archivo-sec" class="d-none">
                    <h6 class="fw-bold">Archivo adjunto:</h6>
                    <a id="d-archivo-link" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-paperclip me-1"></i><span id="d-archivo-nombre"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const colores = {notificacion:'#1B4F8A',instructivo:'#1D6A3A',urgente:'#B71C1C',reunion:'#E65100'};
let paginaActual = 1;

async function cargarTabla(pagina = 1) {
    paginaActual = pagina;
    const tipo  = document.getElementById('filtro-tipo').value;
    const desde = document.getElementById('filtro-desde').value;
    const hasta = document.getElementById('filtro-hasta').value;

    const params = new URLSearchParams({ pagina });
    if (tipo)  params.set('tipo', tipo);
    if (desde) params.set('desde', desde);
    if (hasta) params.set('hasta', hasta);

    const r = await fetch(`{{ route('historial.data') }}?${params}`);
    const d = await r.json();

    const tbody = document.getElementById('tabla-body');
    if (!d.data || !d.data.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Sin resultados</td></tr>';
        document.getElementById('paginacion').innerHTML = '';
        return;
    }

    tbody.innerHTML = d.data.map(m => `
        <tr onclick="verDetalle(${JSON.stringify(m).replace(/"/g,'&quot;')})">
            <td class="ps-3 small text-muted">${m.created_at}</td>
            <td><span class="badge text-white badge-tipo" style="background:${colores[m.tipo]}">${m.tipo}</span></td>
            <td class="fw-semibold">${m.titulo}</td>
            <td class="small">${m.remitente}</td>
            <td>${m.tiene_archivo ? '<i class="fas fa-paperclip text-secondary"></i>' : ''}</td>
            <td><span class="badge bg-secondary">${m.total_pcs_enviado}</span></td>
        </tr>
    `).join('');

    // Paginación
    const pag = document.getElementById('paginacion');
    pag.innerHTML = '';
    for (let i = 1; i <= d.last_page; i++) {
        const btn = document.createElement('button');
        btn.className = `btn btn-sm ${i === d.current_page ? 'btn-mp' : 'btn-outline-secondary'}`;
        btn.textContent = i;
        btn.onclick = () => cargarTabla(i);
        pag.appendChild(btn);
    }
}

function verDetalle(m) {
    const header = document.getElementById('modal-header');
    header.style.background = colores[m.tipo];
    document.getElementById('modal-titulo').textContent = m.titulo;
    document.getElementById('d-tipo').innerHTML = `<span class="badge text-white" style="background:${colores[m.tipo]}">${m.tipo}</span>`;
    document.getElementById('d-remitente').textContent = m.remitente;
    document.getElementById('d-fecha').textContent = m.created_at;
    document.getElementById('d-pcs').textContent = m.total_pcs_enviado + ' PC(s)';
    document.getElementById('d-cuerpo').textContent = m.cuerpo;

    const archSec = document.getElementById('d-archivo-sec');
    if (m.tiene_archivo && m.archivo_nombre) {
        archSec.classList.remove('d-none');
        document.getElementById('d-archivo-nombre').textContent = m.archivo_nombre;
        document.getElementById('d-archivo-link').href = m.archivo_url ?? '#';
    } else {
        archSec.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('modal-detalle')).show();
}

document.getElementById('btn-filtrar').onclick = () => cargarTabla(1);
document.getElementById('btn-limpiar').onclick = () => {
    document.getElementById('filtro-tipo').value = '';
    document.getElementById('filtro-desde').value = '';
    document.getElementById('filtro-hasta').value = '';
    cargarTabla(1);
};

cargarTabla();
</script>
@endpush
