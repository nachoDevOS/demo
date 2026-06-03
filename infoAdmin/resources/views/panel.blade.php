@extends('layouts.app')

@section('title', 'Panel de Envío')

@push('styles')
<style>
    .tipo-btn input[type=radio] { display: none; }
    .tipo-btn label {
        cursor: pointer; padding: .4rem 1.1rem; border-radius: 50px;
        border: 2px solid #dee2e6; font-weight: 600; font-size: .85rem;
        transition: all .2s; user-select: none;
    }
    .tipo-btn input[value=notificacion]:checked + label { background: #1B4F8A; border-color: #1B4F8A; color: #fff; }
    .tipo-btn input[value=instructivo]:checked  + label { background: #1D6A3A; border-color: #1D6A3A; color: #fff; }
    .tipo-btn input[value=urgente]:checked      + label { background: #B71C1C; border-color: #B71C1C; color: #fff; }
    .tipo-btn input[value=reunion]:checked      + label { background: #E65100; border-color: #E65100; color: #fff; }
    #zona-upload {
        border: 2px dashed #b0bec5; border-radius: 10px; padding: 2rem; text-align: center;
        cursor: pointer; transition: all .2s; background: #fafafa;
    }
    #zona-upload.drag-over { border-color: #1B4F8A; background: #E3F0FF; }
    #preview-archivo { max-width: 100%; max-height: 160px; border-radius: 8px; margin-top: .5rem; }
    .historial-mini .badge { font-size: .7rem; }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- Formulario principal --}}
    <div class="col-lg-8">
        <div class="card card-mp">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold" style="color:#1B4F8A;">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Mensaje
                </h5>
                <span class="pc-badge">
                    <i class="fas fa-desktop me-1"></i>
                    <span id="pc-count">...</span> PC(s) conectadas
                </span>
            </div>
            <div class="card-body pt-3">

                {{-- Toast --}}
                <div id="toast-ok" class="alert alert-success d-none">
                    <i class="fas fa-check-circle me-2"></i>
                    Mensaje enviado a <strong id="toast-pcs">0</strong> PC(s) correctamente.
                </div>
                <div id="toast-err" class="alert alert-danger d-none">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="toast-err-msg">Error al enviar.</span>
                </div>

                <form id="form-envio" enctype="multipart/form-data">
                    @csrf

                    {{-- Tipo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de mensaje</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['notificacion'=>'Notificación','instructivo'=>'Instructivo','urgente'=>'Urgente','reunion'=>'Reunión'] as $val => $label)
                            <div class="tipo-btn">
                                <input type="radio" name="tipo" id="tipo_{{ $val }}" value="{{ $val }}"
                                       {{ $val === 'notificacion' ? 'checked' : '' }}>
                                <label for="tipo_{{ $val }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Título --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="titulo" id="titulo" class="form-control"
                               maxlength="200" placeholder="Asunto del mensaje" required>
                        <div class="text-end text-muted small mt-1">
                            <span id="cnt-titulo">0</span>/200
                        </div>
                    </div>

                    {{-- Cuerpo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mensaje <span class="text-danger">*</span></label>
                        <textarea name="cuerpo" id="cuerpo" class="form-control" rows="5"
                                  maxlength="2000" placeholder="Escribe el contenido del mensaje..." required></textarea>
                        <div class="text-end text-muted small mt-1">
                            <span id="cnt-cuerpo">0</span>/2000
                        </div>
                    </div>

                    {{-- Remitente --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remitente</label>
                        <input type="text" name="remitente" class="form-control"
                               value="{{ Auth::user()->name }}" maxlength="100">
                    </div>

                    {{-- Archivo --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Archivo adjunto <small class="text-muted">(PDF, JPG, PNG, GIF — máx. 20MB)</small></label>
                        <div id="zona-upload" onclick="document.getElementById('archivo-input').click()">
                            <i class="fas fa-cloud-upload-alt fa-2x text-secondary mb-2"></i>
                            <p class="mb-0 text-muted">Arrastra un archivo aquí o haz clic para seleccionar</p>
                            <div id="archivo-info" class="mt-2 d-none">
                                <img id="preview-archivo" src="" alt="" class="d-none">
                                <div id="pdf-icon" class="d-none">
                                    <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                </div>
                                <p id="archivo-nombre" class="fw-semibold mb-0 mt-1 small"></p>
                                <p id="archivo-size" class="text-muted mb-0 small"></p>
                                <button type="button" id="btn-quitar" class="btn btn-sm btn-outline-danger mt-2">
                                    <i class="fas fa-times me-1"></i>Quitar archivo
                                </button>
                            </div>
                        </div>
                        <input type="file" id="archivo-input" name="archivo"
                               accept=".pdf,.jpg,.jpeg,.png,.gif" class="d-none">
                    </div>

                    <button type="submit" id="btn-enviar" class="btn btn-mp btn-lg w-100 fw-bold">
                        <span id="btn-text"><i class="fas fa-paper-plane me-2"></i>Enviar a todas las PCs</span>
                        <span id="btn-spinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>Enviando...
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Panel lateral: historial rápido --}}
    <div class="col-lg-4">
        <div class="card card-mp">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-bold" style="color:#1B4F8A;">
                    <i class="fas fa-history me-2"></i>Últimos mensajes
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush historial-mini" id="historial-mini">
                    @forelse($ultimosMensajes as $m)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 me-2">
                                <p class="mb-0 fw-semibold small text-truncate" style="max-width:180px;">{{ $m->titulo }}</p>
                                <small class="text-muted">{{ $m->created_at?->diffForHumans() }}</small>
                            </div>
                            <span class="badge badge-{{ $m->tipo }} text-white">{{ ucfirst($m->tipo) }}</span>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3 small">Sin mensajes aún</li>
                    @endforelse
                </ul>
                <div class="p-2 border-top">
                    <a href="{{ route('historial') }}" class="btn btn-sm btn-outline-secondary w-100">
                        Ver historial completo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Contador de caracteres
document.getElementById('titulo').addEventListener('input', function() {
    document.getElementById('cnt-titulo').textContent = this.value.length;
});
document.getElementById('cuerpo').addEventListener('input', function() {
    document.getElementById('cnt-cuerpo').textContent = this.value.length;
});

// Drag & drop
const zona = document.getElementById('zona-upload');
zona.addEventListener('dragover', e => { e.preventDefault(); zona.classList.add('drag-over'); });
zona.addEventListener('dragleave', () => zona.classList.remove('drag-over'));
zona.addEventListener('drop', e => {
    e.preventDefault();
    zona.classList.remove('drag-over');
    const input = document.getElementById('archivo-input');
    if (e.dataTransfer.files.length) {
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        input.files = dt.files;
        mostrarPreview(e.dataTransfer.files[0]);
    }
});

document.getElementById('archivo-input').addEventListener('change', function() {
    if (this.files.length) mostrarPreview(this.files[0]);
});

function mostrarPreview(file) {
    const info = document.getElementById('archivo-info');
    const img  = document.getElementById('preview-archivo');
    const pdfI = document.getElementById('pdf-icon');
    const nombre = document.getElementById('archivo-nombre');
    const size = document.getElementById('archivo-size');

    info.classList.remove('d-none');
    nombre.textContent = file.name;
    size.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

    if (file.type.startsWith('image/')) {
        img.src = URL.createObjectURL(file);
        img.classList.remove('d-none');
        pdfI.classList.add('d-none');
    } else {
        img.classList.add('d-none');
        pdfI.classList.remove('d-none');
    }
}

document.getElementById('btn-quitar').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('archivo-input').value = '';
    document.getElementById('archivo-info').classList.add('d-none');
    document.getElementById('preview-archivo').src = '';
});

// Envío del formulario
document.getElementById('form-envio').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn    = document.getElementById('btn-enviar');
    const text   = document.getElementById('btn-text');
    const spin   = document.getElementById('btn-spinner');
    const toastOk  = document.getElementById('toast-ok');
    const toastErr = document.getElementById('toast-err');

    btn.disabled = true;
    text.classList.add('d-none');
    spin.classList.remove('d-none');
    toastOk.classList.add('d-none');
    toastErr.classList.add('d-none');

    try {
        const fd = new FormData(this);
        const r  = await fetch('{{ route("panel.enviar") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: fd,
        });
        const data = await r.json();

        if (r.ok && data.ok) {
            document.getElementById('toast-pcs').textContent = data.enviados;
            toastOk.classList.remove('d-none');
            this.reset();
            document.getElementById('cnt-titulo').textContent = '0';
            document.getElementById('cnt-cuerpo').textContent = '0';
            document.getElementById('archivo-info').classList.add('d-none');
            recargarHistorialMini();
        } else {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : 'Error al enviar.';
            document.getElementById('toast-err-msg').textContent = msgs;
            toastErr.classList.remove('d-none');
        }
    } catch (err) {
        document.getElementById('toast-err-msg').textContent = 'Error de conexión.';
        toastErr.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        text.classList.remove('d-none');
        spin.classList.add('d-none');
    }
});

// Contador PCs conectadas (cada 3s)
async function actualizarPcs() {
    try {
        const r = await fetch('{{ route("panel.pcs") }}');
        const d = await r.json();
        document.getElementById('pc-count').textContent = d.count;
    } catch {}
}
actualizarPcs();
setInterval(actualizarPcs, 3000);

async function recargarHistorialMini() {
    try {
        const r = await fetch('{{ route("historial.data") }}');
        const d = await r.json();
        const ul = document.getElementById('historial-mini');
        if (!d.data || !d.data.length) return;
        const badges = {notificacion:'#1B4F8A',instructivo:'#1D6A3A',urgente:'#B71C1C',reunion:'#E65100'};
        ul.innerHTML = d.data.slice(0,5).map(m => `
            <li class="list-group-item px-3 py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1 me-2">
                        <p class="mb-0 fw-semibold small text-truncate" style="max-width:180px;">${m.titulo}</p>
                        <small class="text-muted">${m.created_at}</small>
                    </div>
                    <span class="badge text-white" style="background:${badges[m.tipo]}">${m.tipo}</span>
                </div>
            </li>
        `).join('');
    } catch {}
}
</script>
@endpush
