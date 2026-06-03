@extends('layouts.app')

@section('title', 'Panel de Envío')

@push('styles')
<style>
    /* ── Tipo de mensaje ──────────────────────────────────── */
    .tipo-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    .tipo-btn input[type=radio] { display: none; }
    .tipo-btn label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        padding: .75rem .5rem;
        border-radius: 12px;
        border: 2px solid #E2E8F0;
        font-weight: 600;
        font-size: .78rem;
        transition: all .18s;
        user-select: none;
        background: #F8FAFC;
        color: #64748B;
        width: 100%;
    }
    .tipo-btn label i { font-size: 1.2rem; }
    .tipo-btn label:hover { border-color: #94A3B8; background: #F1F5F9; color: #334155; }
    .tipo-btn input:checked + label {
        border-color: var(--tipo-color);
        color: var(--tipo-color);
        background: color-mix(in srgb, var(--tipo-color) 8%, white);
    }

    /* ── Inputs ──────────────────────────────────────────── */
    .form-control-mp {
        border: 1.5px solid #E2E8F0;
        border-radius: 10px;
        padding: .65rem 1rem;
        font-size: .9rem;
        transition: border-color .18s, box-shadow .18s;
        background: #FAFCFF;
    }
    .form-control-mp:focus {
        border-color: #1B4F8A;
        box-shadow: 0 0 0 3px rgba(27,79,138,.1);
        outline: none;
        background: #fff;
    }
    textarea.form-control-mp { resize: vertical; }

    .char-counter { font-size: .75rem; color: #94A3B8; }
    .form-label-mp { font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }

    /* ── Zona upload ─────────────────────────────────────── */
    #zona-upload {
        border: 2px dashed #CBD5E1;
        border-radius: 12px;
        padding: 1.8rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: #F8FAFC;
    }
    #zona-upload:hover, #zona-upload.drag-over {
        border-color: #1B4F8A;
        background: #EEF4FF;
    }
    #zona-upload .upload-icon {
        width: 48px; height: 48px;
        background: #E8EEF6;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto .8rem;
        font-size: 1.3rem;
        color: #1B4F8A;
        transition: all .2s;
    }
    #zona-upload:hover .upload-icon { background: #D0DFF5; }
    #preview-archivo { max-width: 100%; max-height: 140px; border-radius: 8px; margin-top: .5rem; }

    /* ── Card archivos seleccionado ──────────────────────── */
    .archivo-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #F0F7FF;
        border: 1px solid #BFDBFE;
        border-radius: 10px;
        padding: .7rem 1rem;
        margin-top: .75rem;
    }
    .archivo-card .archivo-icon {
        width: 38px; height: 38px;
        border-radius: 8px;
        background: #DBEAFE;
        display: flex; align-items: center; justify-content: center;
        color: #1B4F8A;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .archivo-card .archivo-meta { flex-grow: 1; min-width: 0; }
    .archivo-card .archivo-nombre { font-size: .83rem; font-weight: 600; color: #1E3A5F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .archivo-card .archivo-size { font-size: .75rem; color: #64748B; }
    .btn-quitar-archivo {
        background: none;
        border: none;
        color: #94A3B8;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        transition: all .15s;
        flex-shrink: 0;
    }
    .btn-quitar-archivo:hover { background: #FEE2E2; color: #B91C1C; }

    /* ── Botón enviar ────────────────────────────────────── */
    #btn-enviar {
        padding: .85rem;
        font-size: 1rem;
        letter-spacing: .3px;
    }

    /* ── Toast ───────────────────────────────────────────── */
    .toast-mp {
        border-radius: 10px;
        border: none;
        font-size: .87rem;
        padding: .75rem 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .toast-mp.ok  { background: #DCFCE7; color: #166534; }
    .toast-mp.err { background: #FEE2E2; color: #991B1B; }
    .toast-mp .toast-icon { font-size: 1rem; }

    /* ── Historial mini ──────────────────────────────────── */
    .hm-item {
        padding: .7rem 1rem;
        border-bottom: 1px solid #F1F5F9;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background .15s;
    }
    .hm-item:last-child { border-bottom: none; }
    .hm-item:hover { background: #F8FAFC; }
    .hm-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .hm-titulo { font-size: .82rem; font-weight: 600; color: #1E293B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
    .hm-meta   { font-size: .72rem; color: #94A3B8; }

    .seccion-titulo {
        font-size: .95rem;
        font-weight: 700;
        color: #1E293B;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .seccion-titulo i { color: #1B4F8A; font-size: .9rem; }
</style>
@endpush

@section('content')
<div class="row g-4">

    {{-- ── Formulario principal ──────────────────────────── --}}
    <div class="col-lg-8">
        <div class="card card-mp">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-3 d-flex align-items-center justify-content-between">
                <span class="seccion-titulo">
                    <i class="fas fa-paper-plane"></i>Nuevo mensaje
                </span>
                <span class="pc-badge" id="pc-badge">
                    <i class="fas fa-circle" style="font-size:.55rem;color:#22C55E;"></i>
                    <span id="pc-count">...</span> PC(s) conectadas
                </span>
            </div>

            <div class="card-body px-4 pb-4 pt-1">

                {{-- Toasts --}}
                <div id="toast-ok" class="toast-mp ok d-none mb-3">
                    <i class="fas fa-check-circle toast-icon"></i>
                    Mensaje enviado a <strong id="toast-pcs" class="mx-1">0</strong> PC(s) correctamente.
                </div>
                <div id="toast-err" class="toast-mp err d-none mb-3">
                    <i class="fas fa-exclamation-circle toast-icon"></i>
                    <span id="toast-err-msg">Error al enviar.</span>
                </div>

                <form id="form-envio" enctype="multipart/form-data">
                    @csrf

                    {{-- Tipo --}}
                    <div class="mb-4">
                        <label class="form-label-mp">Tipo de mensaje</label>
                        <div class="tipo-grid" style="grid-template-columns: repeat({{ min(count($tipos), 4) }}, 1fr);">
                            @foreach($tipos as $i => $t)
                            <div class="tipo-btn">
                                <input type="radio" name="tipo" id="tipo_{{ $t->slug }}" value="{{ $t->slug }}"
                                       {{ $i === 0 ? 'checked' : '' }}
                                       data-color="{{ $t->color }}">
                                <label for="tipo_{{ $t->slug }}"
                                       style="--tipo-color: {{ $t->color }}">
                                    <i class="fas {{ $t->icono }}"></i>{{ $t->nombre }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Título --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label-mp mb-0" for="titulo">Título <span class="text-danger">*</span></label>
                            <span class="char-counter"><span id="cnt-titulo">0</span>/200</span>
                        </div>
                        <input type="text" name="titulo" id="titulo" class="form-control form-control-mp"
                               maxlength="200" placeholder="Asunto del mensaje" required>
                    </div>

                    {{-- Cuerpo --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label-mp mb-0" for="cuerpo">Mensaje <span class="text-danger">*</span></label>
                            <span class="char-counter"><span id="cnt-cuerpo">0</span>/2000</span>
                        </div>
                        <textarea name="cuerpo" id="cuerpo" class="form-control form-control-mp" rows="5"
                                  maxlength="2000" placeholder="Escribe el contenido del mensaje..." required></textarea>
                    </div>

                    {{-- Remitente --}}
                    <div class="mb-4">
                        <label class="form-label-mp" for="remitente">Remitente</label>
                        <input type="text" name="remitente" id="remitente" class="form-control form-control-mp"
                               value="{{ Auth::user()->name }}" maxlength="100">
                    </div>

                    {{-- Archivo --}}
                    <div class="mb-4">
                        <label class="form-label-mp">Archivo adjunto <span class="text-muted fw-normal">(PDF, JPG, PNG, GIF — máx. 20 MB)</span></label>
                        <div id="zona-upload" onclick="document.getElementById('archivo-input').click()">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p class="mb-0 fw-semibold" style="font-size:.85rem;color:#475569;">Arrastrá un archivo o hacé clic para seleccionar</p>
                            <p class="mb-0 mt-1" style="font-size:.75rem;color:#94A3B8;">PDF, JPG, PNG, GIF</p>
                        </div>
                        <input type="file" id="archivo-input" name="archivo"
                               accept=".pdf,.jpg,.jpeg,.png,.gif" class="d-none">

                        <div id="archivo-info" class="d-none">
                            <div class="archivo-card">
                                <div class="archivo-icon">
                                    <i id="archivo-icon-tipo" class="fas fa-file"></i>
                                </div>
                                <div class="archivo-meta">
                                    <div class="archivo-nombre" id="archivo-nombre"></div>
                                    <div class="archivo-size" id="archivo-size"></div>
                                </div>
                                <button type="button" class="btn-quitar-archivo" id="btn-quitar" title="Quitar archivo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <img id="preview-archivo" src="" alt="" class="d-none mt-2 ms-1" style="max-height:120px;border-radius:8px;">
                        </div>
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

    {{-- ── Panel lateral ─────────────────────────────────── --}}
    <div class="col-lg-4 d-flex flex-column gap-4">

        {{-- Stats rápido --}}
        <div class="card card-mp">
            <div class="card-body px-4 py-3">
                <p class="form-label-mp mb-3">Actividad reciente</p>
                <div id="historial-mini">
                    @forelse($ultimosMensajes as $m)
                    @php
                        $dotColors = ['notificacion'=>'#1B4F8A','instructivo'=>'#1D6A3A','urgente'=>'#B71C1C','reunion'=>'#E65100'];
                        $dot = $dotColors[$m->tipo] ?? '#1B4F8A';
                    @endphp
                    <div class="hm-item">
                        <div class="hm-dot" style="background:{{ $dot }};"></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="hm-titulo">{{ $m->titulo }}</div>
                            <div class="hm-meta">{{ $m->created_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-muted small py-2 mb-0">Sin mensajes aún</p>
                    @endforelse
                </div>
                <div class="mt-3 pt-2 border-top">
                    <a href="{{ route('historial') }}"
                       class="btn btn-sm w-100 fw-semibold"
                       style="border:1.5px solid #E2E8F0;border-radius:8px;color:#475569;font-size:.8rem;">
                        <i class="fas fa-history me-1"></i>Ver historial completo
                    </a>
                </div>
            </div>
        </div>

        {{-- Acceso rápido confirmaciones --}}
        <div class="card card-mp">
            <div class="card-body px-4 py-3 d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;background:#EEF4FF;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-check-double" style="color:#1B4F8A;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:.85rem;color:#1E293B;">Confirmaciones</div>
                    <div style="font-size:.75rem;color:#94A3B8;">Ver estado de entrega</div>
                </div>
                <a href="{{ route('confirmaciones') }}"
                   class="btn btn-sm btn-mp"
                   style="border-radius:8px;font-size:.78rem;padding:.35rem .9rem;box-shadow:none;">
                    Ver
                </a>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

document.getElementById('titulo').addEventListener('input', function() {
    document.getElementById('cnt-titulo').textContent = this.value.length;
});
document.getElementById('cuerpo').addEventListener('input', function() {
    document.getElementById('cnt-cuerpo').textContent = this.value.length;
});

// Drag & drop
const zona = document.getElementById('zona-upload');
zona.addEventListener('dragover',  e => { e.preventDefault(); zona.classList.add('drag-over'); });
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
    document.getElementById('archivo-info').classList.remove('d-none');
    document.getElementById('archivo-nombre').textContent = file.name;
    document.getElementById('archivo-size').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

    const ico = document.getElementById('archivo-icon-tipo');
    const img = document.getElementById('preview-archivo');

    if (file.type.startsWith('image/')) {
        ico.className = 'fas fa-file-image';
        img.src = URL.createObjectURL(file);
        img.classList.remove('d-none');
    } else if (file.type === 'application/pdf') {
        ico.className = 'fas fa-file-pdf';
        img.classList.add('d-none');
    } else {
        ico.className = 'fas fa-file';
        img.classList.add('d-none');
    }
}

document.getElementById('btn-quitar').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('archivo-input').value = '';
    document.getElementById('archivo-info').classList.add('d-none');
    document.getElementById('preview-archivo').src = '';
});

// Envío
document.getElementById('form-envio').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn     = document.getElementById('btn-enviar');
    const text    = document.getElementById('btn-text');
    const spin    = document.getElementById('btn-spinner');
    const toastOk = document.getElementById('toast-ok');
    const toastEr = document.getElementById('toast-err');

    btn.disabled = true;
    text.classList.add('d-none');
    spin.classList.remove('d-none');
    toastOk.classList.add('d-none');
    toastEr.classList.add('d-none');

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
            toastEr.classList.remove('d-none');
        }
    } catch {
        document.getElementById('toast-err-msg').textContent = 'Error de conexión.';
        toastEr.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        text.classList.remove('d-none');
        spin.classList.add('d-none');
    }
});

// PC counter
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
        if (!d.data || !d.data.length) return;
        const dots = {notificacion:'#1B4F8A',instructivo:'#1D6A3A',urgente:'#B71C1C',reunion:'#E65100'};
        document.getElementById('historial-mini').innerHTML = d.data.slice(0,5).map(m => `
            <div class="hm-item">
                <div class="hm-dot" style="background:${dots[m.tipo]||'#1B4F8A'};"></div>
                <div class="flex-grow-1">
                    <div class="hm-titulo">${m.titulo}</div>
                    <div class="hm-meta">${m.created_at}</div>
                </div>
            </div>
        `).join('');
    } catch {}
}
</script>
@endpush
