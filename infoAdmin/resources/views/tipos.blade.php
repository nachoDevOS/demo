@extends('layouts.app')

@section('title', 'Tipos de Mensaje')

@push('styles')
<style>
    .tipo-card {
        background: #fff;
        border-radius: 12px;
        border: 1.5px solid #E2E8F0;
        padding: 1rem 1.2rem;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: box-shadow .15s;
    }
    .tipo-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.08); }

    .tipo-dot {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .tipo-nombre { font-weight: 700; font-size: .92rem; color: #1E293B; }
    .tipo-slug   { font-size: .75rem; color: #94A3B8; font-family: monospace; }

    .btn-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        border: 1.5px solid #E2E8F0;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        font-size: .8rem;
        transition: all .15s;
        color: #64748B;
    }
    .btn-icon:hover.edit  { border-color: #1B4F8A; color: #1B4F8A; background: #EEF4FF; }
    .btn-icon:hover.del   { border-color: #B91C1C; color: #B91C1C; background: #FEF2F2; }
    .btn-icon:hover.toggle { border-color: #15803D; color: #15803D; background: #F0FDF4; }

    .form-control-mp {
        border: 1.5px solid #E2E8F0;
        border-radius: 10px;
        padding: .6rem 1rem;
        font-size: .88rem;
        transition: border-color .18s, box-shadow .18s;
    }
    .form-control-mp:focus {
        border-color: #1B4F8A;
        box-shadow: 0 0 0 3px rgba(27,79,138,.1);
        outline: none;
    }
    .form-label-mp { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }

    .inactivo-overlay { opacity: .5; }

    .seccion-titulo {
        font-size: .95rem; font-weight: 700; color: #1E293B;
        display: flex; align-items: center; gap: 8px;
    }
    .seccion-titulo i { color: #1B4F8A; }

    /* Modal editar */
    .modal-mp .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 8px 40px rgba(0,0,0,.15);
    }
    .modal-mp .modal-header {
        border-bottom: 1px solid #F1F5F9;
        padding: 1.2rem 1.5rem .8rem;
    }
    .modal-mp .modal-footer {
        border-top: 1px solid #F1F5F9;
        padding: .8rem 1.5rem 1.2rem;
    }

    .btn-mp {
        background: linear-gradient(135deg, #1B4F8A 0%, #2563EB 100%);
        border: none; color: #fff; border-radius: 10px; font-weight: 600;
        transition: all .2s; box-shadow: 0 4px 14px rgba(27,79,138,.3);
    }
    .btn-mp:hover { background: linear-gradient(135deg,#123566,#1B4F8A); color:#fff; transform:translateY(-1px); }
    .btn-mp:active { transform: translateY(0); }

    #color-preview {
        width: 36px; height: 36px; border-radius: 8px;
        flex-shrink: 0; border: 2px solid #E2E8F0;
        transition: background .15s;
    }
</style>
@endpush

@section('content')
<div class="row g-4">

    {{-- ── Lista de tipos ─────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card card-mp">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-3">
                <span class="seccion-titulo">
                    <i class="fas fa-tags"></i>Tipos de mensaje registrados
                </span>
            </div>
            <div class="card-body px-4 pb-4 pt-1">
                <div id="lista-tipos" class="d-flex flex-column gap-2">
                    @forelse($tipos as $t)
                    <div class="tipo-card {{ $t->activo ? '' : 'inactivo-overlay' }}" id="tipo-row-{{ $t->id }}">
                        <div class="tipo-dot" style="background: {{ $t->color }}">
                            <i class="fas {{ $t->icono }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="tipo-nombre">{{ $t->nombre }}</div>
                            <div class="tipo-slug">{{ $t->slug }}</div>
                        </div>
                        <div style="font-size:.72rem;color:#94A3B8;" class="me-1">
                            {{ $t->activo ? 'Activo' : 'Inactivo' }}
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn-icon toggle" title="{{ $t->activo ? 'Desactivar' : 'Activar' }}"
                                    onclick="toggleTipo({{ $t->id }}, {{ $t->activo ? 'false' : 'true' }})">
                                <i class="fas {{ $t->activo ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                            </button>
                            <button class="btn-icon edit" title="Editar"
                                    onclick="abrirEditar({{ $t->id }}, '{{ addslashes($t->nombre) }}', '{{ $t->color }}', '{{ $t->icono }}', {{ $t->activo ? 'true' : 'false' }})">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <button class="btn-icon del" title="Eliminar"
                                    onclick="eliminar({{ $t->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3">Sin tipos registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── Formulario nuevo tipo ──────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card card-mp">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-3">
                <span class="seccion-titulo">
                    <i class="fas fa-plus-circle"></i>Nuevo tipo
                </span>
            </div>
            <div class="card-body px-4 pb-4 pt-1">

                <div id="toast-nuevo-ok" class="d-none mb-3 p-3 rounded-3"
                     style="background:#DCFCE7;color:#166534;font-size:.85rem;">
                    <i class="fas fa-check-circle me-2"></i>Tipo creado correctamente.
                </div>
                <div id="toast-nuevo-err" class="d-none mb-3 p-3 rounded-3"
                     style="background:#FEE2E2;color:#991B1B;font-size:.85rem;">
                    <span id="msg-nuevo-err">Error.</span>
                </div>

                <form id="form-nuevo">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-mp">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control form-control-mp"
                               maxlength="100" placeholder="Ej: Comunicado" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-mp">Slug <span class="text-danger">*</span>
                            <span class="fw-normal text-muted">(sin espacios, solo letras/guiones)</span>
                        </label>
                        <input type="text" name="slug" id="slug-input" class="form-control form-control-mp"
                               maxlength="50" placeholder="Ej: comunicado" pattern="[a-z0-9_\-]+" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-mp">Color</label>
                        <div class="d-flex gap-2 align-items-center">
                            <div id="color-preview" style="background:#1B4F8A;"></div>
                            <input type="color" name="color" id="color-input" value="#1B4F8A"
                                   class="form-control form-control-mp" style="width:100%;height:42px;padding:2px 6px;cursor:pointer;">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-mp">Ícono <span class="fw-normal text-muted">(clase Font Awesome)</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <div id="icono-preview" style="width:36px;height:36px;border-radius:8px;background:#E2E8F0;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;color:#475569;">
                                <i class="fas fa-bell"></i>
                            </div>
                            <input type="text" name="icono" id="icono-input" class="form-control form-control-mp"
                                   value="fa-bell" placeholder="fa-bell" required>
                        </div>
                        <small class="text-muted" style="font-size:.73rem;">
                            Ejemplos: fa-bell, fa-clipboard-list, fa-exclamation-triangle, fa-calendar-alt, fa-bullhorn
                        </small>
                    </div>
                    <button type="submit" class="btn btn-mp w-100 fw-bold" style="padding:.75rem;">
                        <i class="fas fa-plus me-2"></i>Crear tipo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal editar ──────────────────────────────────────────────────────────────── --}}
<div class="modal fade modal-mp" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="color:#1E293B;">
                    <i class="fas fa-pencil-alt me-2" style="color:#1B4F8A;"></i>Editar tipo
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" id="edit-id">
                <div class="mb-3">
                    <label class="form-label-mp">Nombre</label>
                    <input type="text" id="edit-nombre" class="form-control form-control-mp" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label-mp">Color</label>
                    <div class="d-flex gap-2 align-items-center">
                        <div id="edit-color-preview" style="width:36px;height:36px;border-radius:8px;flex-shrink:0;border:2px solid #E2E8F0;"></div>
                        <input type="color" id="edit-color" class="form-control form-control-mp" style="height:42px;padding:2px 6px;cursor:pointer;">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label-mp">Ícono</label>
                    <div class="d-flex gap-2 align-items-center">
                        <div id="edit-icono-preview" style="width:36px;height:36px;border-radius:8px;background:#E2E8F0;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;color:#475569;">
                            <i id="edit-icono-preview-i" class="fas fa-bell"></i>
                        </div>
                        <input type="text" id="edit-icono" class="form-control form-control-mp" placeholder="fa-bell">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #E2E8F0;border-radius:8px;color:#475569;padding:.4rem 1rem;">
                    Cancelar
                </button>
                <button class="btn btn-mp btn-sm" id="btn-guardar-editar"
                        style="padding:.4rem 1.2rem;box-shadow:none;">
                    <i class="fas fa-save me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

// Preview color en nuevo
document.getElementById('color-input').addEventListener('input', function() {
    document.getElementById('color-preview').style.background = this.value;
});

// Preview ícono en nuevo
document.getElementById('icono-input').addEventListener('input', function() {
    const prev = document.getElementById('icono-preview');
    prev.innerHTML = `<i class="fas ${this.value}"></i>`;
});

// Auto-slug desde nombre
document.querySelector('[name=nombre]').addEventListener('input', function() {
    const slug = document.getElementById('slug-input');
    if (!slug._editado) {
        slug.value = this.value.toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/\s+/g, '-').replace(/[^a-z0-9\-_]/g, '');
    }
});
document.getElementById('slug-input').addEventListener('input', function() {
    this._editado = true;
});

// Crear nuevo tipo
document.getElementById('form-nuevo').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd   = new FormData(this);
    const toOk = document.getElementById('toast-nuevo-ok');
    const toEr = document.getElementById('toast-nuevo-err');
    toOk.classList.add('d-none');
    toEr.classList.add('d-none');

    try {
        const r = await fetch('{{ route("tipos.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: fd,
        });
        const d = await r.json();
        if (r.ok && d.ok) {
            toOk.classList.remove('d-none');
            this.reset();
            document.getElementById('color-preview').style.background = '#1B4F8A';
            document.getElementById('icono-preview').innerHTML = '<i class="fas fa-bell"></i>';
            document.getElementById('slug-input')._editado = false;
            agregarFilaTipo(d.tipo);
        } else {
            const msgs = d.errors ? Object.values(d.errors).flat().join(' · ') : 'Error al crear.';
            document.getElementById('msg-nuevo-err').textContent = msgs;
            toEr.classList.remove('d-none');
        }
    } catch {
        document.getElementById('msg-nuevo-err').textContent = 'Error de conexión.';
        toEr.classList.remove('d-none');
    }
});

function agregarFilaTipo(t) {
    const lista = document.getElementById('lista-tipos');
    const div   = document.createElement('div');
    div.id = `tipo-row-${t.id}`;
    div.className = 'tipo-card';
    div.innerHTML = `
        <div class="tipo-dot" style="background:${t.color}">
            <i class="fas ${t.icono}"></i>
        </div>
        <div class="flex-grow-1">
            <div class="tipo-nombre">${t.nombre}</div>
            <div class="tipo-slug">${t.slug}</div>
        </div>
        <div style="font-size:.72rem;color:#94A3B8;" class="me-1">Activo</div>
        <div class="d-flex gap-1">
            <button class="btn-icon toggle" title="Desactivar" onclick="toggleTipo(${t.id}, false)">
                <i class="fas fa-eye-slash"></i>
            </button>
            <button class="btn-icon edit" title="Editar"
                    onclick="abrirEditar(${t.id}, '${t.nombre.replace(/'/g,"\\'")}', '${t.color}', '${t.icono}', true)">
                <i class="fas fa-pencil-alt"></i>
            </button>
            <button class="btn-icon del" title="Eliminar" onclick="eliminar(${t.id})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    lista.appendChild(div);
}

async function eliminar(id) {
    if (!confirm('¿Eliminar este tipo? Los mensajes existentes conservan el tipo como texto.')) return;
    try {
        const r = await fetch(`/tipos/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        const d = await r.json();
        if (d.ok) document.getElementById(`tipo-row-${id}`)?.remove();
    } catch {}
}

async function toggleTipo(id, nuevoActivo) {
    try {
        const row = document.getElementById(`tipo-row-${id}`);
        const r   = await fetch(`/tipos/${id}`, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ activo: nuevoActivo }),
        });
        const d = await r.json();
        if (d.ok) {
            // Refrescar fila — más simple que manipular DOM
            location.reload();
        }
    } catch {}
}

// Modal editar
let _editModal;
function abrirEditar(id, nombre, color, icono, activo) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nombre').value = nombre;
    document.getElementById('edit-color').value = color;
    document.getElementById('edit-color-preview').style.background = color;
    document.getElementById('edit-icono').value = icono;
    document.getElementById('edit-icono-preview-i').className = `fas ${icono}`;

    _editModal = _editModal || new bootstrap.Modal(document.getElementById('modalEditar'));
    _editModal.show();
}

document.getElementById('edit-color').addEventListener('input', function() {
    document.getElementById('edit-color-preview').style.background = this.value;
});
document.getElementById('edit-icono').addEventListener('input', function() {
    document.getElementById('edit-icono-preview-i').className = `fas ${this.value}`;
});

document.getElementById('btn-guardar-editar').addEventListener('click', async function() {
    const id = document.getElementById('edit-id').value;
    const body = {
        nombre: document.getElementById('edit-nombre').value,
        color:  document.getElementById('edit-color').value,
        icono:  document.getElementById('edit-icono').value,
    };
    try {
        const r = await fetch(`/tipos/${id}`, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const d = await r.json();
        if (d.ok) {
            _editModal.hide();
            location.reload();
        }
    } catch {}
});
</script>
@endpush
