# infoDesk — Ventana emergente con visor de PDF e imágenes
# Maneja la cola de mensajes y muestra notificaciones al usuario.

import io
import logging
import os
import subprocess
import sys
import threading
import time
import tkinter as tk
from pathlib import Path
from tkinter import messagebox, filedialog

import requests

try:
    from PIL import Image, ImageTk
    PIL_OK = True
except ImportError:
    PIL_OK = False

try:
    import fitz  # PyMuPDF
    PYMUPDF_OK = True
except ImportError:
    PYMUPDF_OK = False

log = logging.getLogger('mensadesk.ventana')


def _get_resource_path(filename: str) -> Path:
    """Busca el archivo primero junto al exe, luego en el bundle PyInstaller."""
    if getattr(sys, 'frozen', False):
        externo = Path(sys.executable).parent / filename
        if externo.exists():
            return externo
        return Path(sys._MEIPASS) / filename
    return Path(__file__).parent / filename


def _reproducir_sonido():
    """Reproduce notification.mp3 en background usando Windows MediaPlayer."""
    ruta = _get_resource_path('notification.mp3')
    if not ruta.exists():
        return

    def _play():
        try:
            ruta_fwd = str(ruta).replace('\\', '/')
            cmd = (
                '[System.Reflection.Assembly]::LoadWithPartialName("presentationCore") | Out-Null; '
                f'$p = New-Object System.Windows.Media.MediaPlayer; '
                f'$p.Open([uri]"{ruta_fwd}"); '
                f'$p.Play(); '
                'Start-Sleep -Milliseconds 4000'
            )
            subprocess.run(
                ['powershell', '-WindowStyle', 'Hidden', '-NonInteractive', '-Command', cmd],
                creationflags=subprocess.CREATE_NO_WINDOW,
                timeout=8,
            )
        except Exception as e:
            log.warning(f"Error reproduciendo sonido: {e}")

    threading.Thread(target=_play, daemon=True).start()


# Colores según tipo de mensaje
COLORES_TIPO = {
    'notificacion': '#1B4F8A',
    'instructivo':  '#1D6A3A',
    'urgente':      '#B71C1C',
    'reunion':      '#E65100',
}

ICONOS_TIPO = {
    'notificacion': '🔔',
    'instructivo':  '📋',
    'urgente':      '🚨',
    'reunion':      '📅',
}


def _carpeta_descargas() -> Path:
    """Devuelve la carpeta Descargas real del usuario vía registro de Windows."""
    try:
        import winreg
        key = winreg.OpenKey(
            winreg.HKEY_CURRENT_USER,
            r'Software\Microsoft\Windows\CurrentVersion\Explorer\Shell Folders',
        )
        val, _ = winreg.QueryValueEx(key, '{374DE290-123F-4565-9164-39C4925E467B}')
        winreg.CloseKey(key)
        p = Path(val)
        if p.exists():
            return p
    except Exception:
        pass
    # Fallback si el registro falla
    home = Path.home()
    for nombre in ['Downloads', 'Descargas']:
        p = home / nombre
        if p.exists():
            return p
    return home


class VentanaMensaje:
    """Ventana emergente que muestra un mensaje del servidor."""

    def __init__(self, root: tk.Tk, datos: dict, pc_nombre: str,
                 api_url: str, enviar_confirmacion_fn, al_cerrar_fn):
        self.root              = root
        self.datos             = datos
        self.pc_nombre         = pc_nombre
        self.api_url           = api_url
        self.enviar_conf       = enviar_confirmacion_fn
        self.al_cerrar_fn      = al_cerrar_fn
        self.imagen_refs        = []  # Evitar GC de imágenes tkinter
        self._confirmado       = False

        self._crear_ventana()
        self._enviar_recibido()
        log.info(f"Ventana creada: {self.datos.get('titulo', '')}")

    def _enviar_recibido(self):
        """Envía confirmación de recibido al aparecer la ventana."""
        mid = self.datos.get('mensaje_id')
        if mid:
            threading.Thread(
                target=self.enviar_conf, args=(mid, 'recibido'), daemon=True
            ).start()

    def _crear_ventana(self):
        tipo   = self.datos.get('tipo', 'notificacion')
        titulo = self.datos.get('titulo', 'Mensaje')
        cuerpo = self.datos.get('cuerpo', '')
        remitente = self.datos.get('remitente', '')
        timestamp = self.datos.get('timestamp', '')
        tiene_archivo = self.datos.get('tiene_archivo', False)
        archivo_url   = self.datos.get('archivo_url')
        archivo_tipo  = self.datos.get('archivo_tipo')
        archivo_nombre = self.datos.get('archivo_nombre', 'archivo')

        color = COLORES_TIPO.get(tipo, '#1B4F8A')
        icono = ICONOS_TIPO.get(tipo, '📨')

        # Crear ventana secundaria (Toplevel)
        self.win = tk.Toplevel(self.root)
        self.win.title('infoDesk')
        self.win.overrideredirect(True)  # Sin barra de título ni botones de Windows
        self.win.attributes('-topmost', True)
        self.win.configure(bg='#FFFFFF')

        # Centrar en pantalla con fallback seguro
        w, h = 520, 600
        self.win.update_idletasks()
        sw = self.win.winfo_screenwidth()  or 1920
        sh = self.win.winfo_screenheight() or 1080
        x = max(0, (sw - w) // 2)
        y = max(0, (sh - h) // 2)
        self.win.geometry(f'{w}x{h}+{x}+{y}')
        self.win.resizable(False, True)

        # Reproducir sonido de notificación
        _reproducir_sonido()

        # ── Header ────────────────────────────────────────────────────────────
        header = tk.Frame(self.win, bg=color, height=65)
        header.pack(fill='x')
        header.pack_propagate(False)

        # Ícono del header — usa icono.ico si está disponible, si no usa emoji
        if PIL_OK:
            ico_path = _get_resource_path('icono.ico')
            try:
                ico_img = Image.open(str(ico_path)).resize((26, 26), Image.LANCZOS)
                ico_photo = ImageTk.PhotoImage(ico_img)
                self.imagen_refs.append(ico_photo)
                tk.Label(header, image=ico_photo, bg=color).pack(side='left', padx=(14, 4), pady=15)
            except Exception:
                tk.Label(header, text=icono, bg=color, fg='white',
                         font=('Segoe UI', 13)).pack(side='left', padx=(14, 4), pady=15)
        else:
            tk.Label(header, text=icono, bg=color, fg='white',
                     font=('Segoe UI', 13)).pack(side='left', padx=(14, 4), pady=15)

        tk.Label(header, text=tipo.upper(),
                 bg=color, fg='white', font=('Segoe UI', 10, 'bold')).pack(side='left', pady=15)

        btn_x = tk.Button(header, text='✕', bg=color, fg='white',
                          font=('Segoe UI', 12), relief='flat', cursor='hand2',
                          command=self._ver_mas_tarde)
        btn_x.pack(side='right', padx=12)

        # ── Contenido (scrollable) ─────────────────────────────────────────────
        contenedor = tk.Frame(self.win, bg='#FFFFFF')
        contenedor.pack(fill='both', expand=True)

        canvas = tk.Canvas(contenedor, bg='#FFFFFF', highlightthickness=0)
        scroll = tk.Scrollbar(contenedor, orient='vertical', command=canvas.yview)
        canvas.configure(yscrollcommand=scroll.set)
        scroll.pack(side='right', fill='y')
        canvas.pack(side='left', fill='both', expand=True)

        self.frame_scroll = tk.Frame(canvas, bg='#FFFFFF')
        win_id = canvas.create_window((0, 0), window=self.frame_scroll, anchor='nw')

        def _ajustar_scroll(e):
            canvas.configure(scrollregion=canvas.bbox('all'))
        def _ajustar_ancho(e):
            canvas.itemconfig(win_id, width=e.width)

        self.frame_scroll.bind('<Configure>', _ajustar_scroll)
        canvas.bind('<Configure>', _ajustar_ancho)

        # Mouse wheel
        def _scroll_mouse(e):
            canvas.yview_scroll(int(-1 * (e.delta / 120)), 'units')
        canvas.bind_all('<MouseWheel>', _scroll_mouse)

        # Título del mensaje
        tk.Label(self.frame_scroll, text=titulo, wraplength=470,
                 bg='#FFFFFF', fg='#1A1A2E',
                 font=('Segoe UI', 13, 'bold'), justify='left', anchor='w'
                 ).pack(fill='x', padx=18, pady=(16, 4))

        # Remitente y hora
        tk.Label(self.frame_scroll,
                 text=f"De: {remitente}   —   {timestamp}",
                 bg='#FFFFFF', fg='#888', font=('Segoe UI', 9),
                 anchor='w'
                 ).pack(fill='x', padx=18, pady=(0, 8))

        # Separador
        tk.Frame(self.frame_scroll, bg='#E0E0E0', height=1).pack(fill='x', padx=18)

        # Cuerpo del mensaje
        txt_cuerpo = tk.Text(self.frame_scroll, wrap='word', height=6,
                             font=('Segoe UI', 10), bg='#F8F9FA',
                             fg='#333', relief='flat', padx=10, pady=8,
                             state='disabled', cursor='arrow')
        txt_cuerpo.pack(fill='x', padx=18, pady=10)
        txt_cuerpo.configure(state='normal')
        txt_cuerpo.insert('end', cuerpo)
        txt_cuerpo.configure(state='disabled')

        # ── Archivo adjunto ───────────────────────────────────────────────────
        if tiene_archivo and archivo_url:
            tk.Frame(self.frame_scroll, bg='#E0E0E0', height=1).pack(fill='x', padx=18, pady=(4, 0))
            tk.Label(self.frame_scroll, text='📎  Archivo adjunto',
                     bg='#FFFFFF', fg='#555', font=('Segoe UI', 9, 'bold')
                     ).pack(anchor='w', padx=18, pady=(8, 4))

            # Spinner de carga
            self.lbl_carga = tk.Label(self.frame_scroll, text='⏳ Descargando archivo...',
                                      bg='#FFFFFF', fg='#999', font=('Segoe UI', 9))
            self.lbl_carga.pack(anchor='w', padx=18)

            self.frame_archivo = tk.Frame(self.frame_scroll, bg='#FFFFFF')
            self.frame_archivo.pack(fill='x', padx=18, pady=4)

            # Descargar en hilo aparte
            threading.Thread(
                target=self._descargar_y_mostrar,
                args=(archivo_url, archivo_tipo, archivo_nombre),
                daemon=True,
            ).start()

        # ── Botones ────────────────────────────────────────────────────────────
        frame_btns = tk.Frame(self.win, bg='#F0F4F8', pady=10)
        frame_btns.pack(fill='x', side='bottom')

        tk.Button(
            frame_btns, text='✔  Confirmar lectura',
            bg=color, fg='white', font=('Segoe UI', 10, 'bold'),
            relief='flat', cursor='hand2', padx=18, pady=8,
            command=self._confirmar_lectura,
        ).pack(side='left', padx=(16, 8))

        tk.Button(
            frame_btns, text='🕐  Ver más tarde',
            bg='#9E9E9E', fg='white', font=('Segoe UI', 10),
            relief='flat', cursor='hand2', padx=18, pady=8,
            command=self._ver_mas_tarde,
        ).pack(side='left')

        self.win.lift()
        self.win.focus_force()

    def _descargar_y_mostrar(self, url: str, archivo_tipo: str, archivo_nombre: str):
        """Descarga el archivo y actualiza la UI en el hilo principal."""
        try:
            r = requests.get(url, timeout=30)
            r.raise_for_status()
            data = r.content
        except Exception as e:
            log.warning(f"Error descargando archivo: {e}")
            self.win.after(0, lambda: self.lbl_carga.config(
                text='⚠ No se pudo descargar el archivo.', fg='#B71C1C'))
            return

        self.win.after(0, lambda: self._mostrar_archivo(data, archivo_tipo, archivo_nombre, url))

    def _mostrar_archivo(self, data: bytes, archivo_tipo: str, archivo_nombre: str, url: str):
        """Muestra imagen o páginas PDF en la ventana."""
        try:
            self.lbl_carga.destroy()
        except Exception:
            pass

        mid = self.datos.get('mensaje_id')

        if archivo_tipo == 'imagen' and PIL_OK:
            try:
                img = Image.open(io.BytesIO(data))
                img.thumbnail((480, 300))
                photo = ImageTk.PhotoImage(img)
                self.imagen_refs.append(photo)
                tk.Label(self.frame_archivo, image=photo, bg='#FFFFFF').pack()

                def _descargar_img():
                    try:
                        carpeta_str = filedialog.askdirectory(
                            parent=self.win,
                            title='Seleccionar carpeta donde guardar la imagen',
                            initialdir=str(_carpeta_descargas()),
                        )
                        if not carpeta_str:
                            return
                        ruta = Path(carpeta_str) / archivo_nombre
                        ruta.write_bytes(data)
                        messagebox.showinfo('infoDesk', f'Imagen guardada en:\n{ruta}', parent=self.win)
                        if mid:
                            threading.Thread(
                                target=self.enviar_conf, args=(mid, 'descargado'), daemon=True
                            ).start()
                    except Exception as e:
                        messagebox.showerror('infoDesk', f'Error al guardar imagen:\n{e}', parent=self.win)

                tk.Button(self.frame_archivo, text='⬇  Descargar imagen',
                          font=('Segoe UI', 9), relief='flat', cursor='hand2',
                          bg='#1B4F8A', fg='white', padx=10, pady=4,
                          command=_descargar_img).pack(anchor='w', pady=6)
            except Exception as e:
                log.error(f"Error mostrando imagen: {e}")

        elif archivo_tipo == 'pdf' and PYMUPDF_OK:
            try:
                doc    = fitz.open(stream=data, filetype='pdf')
                paginas = min(3, len(doc))
                for i in range(paginas):
                    page = doc.load_page(i)
                    mat  = fitz.Matrix(1.2, 1.2)
                    pix  = page.get_pixmap(matrix=mat)
                    img  = Image.frombytes('RGB', [pix.width, pix.height], pix.samples)
                    img.thumbnail((480, 620))
                    photo = ImageTk.PhotoImage(img)
                    self.imagen_refs.append(photo)
                    lbl = tk.Label(self.frame_archivo, image=photo, bg='#F0F0F0',
                                   relief='ridge', bd=1)
                    lbl.pack(pady=(0, 4))
                    tk.Label(self.frame_archivo,
                             text=f'Página {i+1} de {len(doc)}',
                             bg='#FFFFFF', fg='#888', font=('Segoe UI', 8)).pack()
                doc.close()

                def _descargar_pdf():
                    try:
                        carpeta_str = filedialog.askdirectory(
                            parent=self.win,
                            title='Seleccionar carpeta donde guardar el PDF',
                            initialdir=str(_carpeta_descargas()),
                        )
                        if not carpeta_str:
                            return  # Usuario canceló
                        ruta = Path(carpeta_str) / archivo_nombre
                        ruta.write_bytes(data)
                        messagebox.showinfo('infoDesk', f'PDF guardado en:\n{ruta}', parent=self.win)
                        if mid:
                            threading.Thread(
                                target=self.enviar_conf, args=(mid, 'descargado'), daemon=True
                            ).start()
                    except Exception as e:
                        messagebox.showerror('infoDesk', f'Error al guardar PDF:\n{e}', parent=self.win)

                tk.Button(self.frame_archivo, text='⬇  Descargar PDF',
                          font=('Segoe UI', 10, 'bold'), relief='flat', cursor='hand2',
                          bg='#1B4F8A', fg='white', padx=16, pady=6,
                          command=_descargar_pdf).pack(anchor='w', pady=6)

            except Exception as e:
                log.error(f"Error mostrando PDF: {e}")
                tk.Label(self.frame_archivo,
                         text=f'📄 {archivo_nombre}\n(No se pudo previsualizar)',
                         bg='#FFFFFF', fg='#555').pack()
        else:
            # Sin previsualización: solo botón de descarga
            def _descargar_gen():
                ruta = _carpeta_descargas() / archivo_nombre
                ruta.write_bytes(data)
                messagebox.showinfo('infoDesk', f'Archivo guardado en:\n{ruta}', parent=self.win)
                if mid:
                    threading.Thread(target=self.enviar_conf, args=(mid, 'descargado'), daemon=True).start()

            tk.Label(self.frame_archivo, text=f'📎 {archivo_nombre}',
                     bg='#FFFFFF', fg='#333', font=('Segoe UI', 9)).pack(anchor='w')
            tk.Button(self.frame_archivo, text='⬇  Descargar',
                      font=('Segoe UI', 9), relief='flat', cursor='hand2',
                      bg='#1B4F8A', fg='white', padx=10, pady=4,
                      command=_descargar_gen).pack(anchor='w', pady=4)

    def _confirmar_lectura(self):
        mid = self.datos.get('mensaje_id')
        if mid and not self._confirmado:
            self._confirmado = True
            threading.Thread(
                target=self.enviar_conf, args=(mid, 'visto'), daemon=True
            ).start()
        self.cerrar()

    def _ver_mas_tarde(self):
        """Minimiza la ventana al tray sin confirmar."""
        self.cerrar()

    def cerrar(self):
        try:
            self.win.destroy()
        except Exception:
            pass
        self.al_cerrar_fn()


# ─── Ventana historial / bandeja ──────────────────────────────────────────────
def _marcar_leido_en_bandeja(bandeja_path, mensaje_id):
    """Marca un mensaje como leído en bandeja.json por mensaje_id."""
    try:
        import json as _json
        p = Path(bandeja_path)
        if not p.exists():
            return
        bandeja = _json.loads(p.read_text(encoding='utf-8'))
        for m in bandeja:
            if m.get('mensaje_id') == mensaje_id:
                m['_leido'] = True
                break
        p.write_text(_json.dumps(bandeja, ensure_ascii=False, indent=2), encoding='utf-8')
    except Exception as e:
        log.warning(f"Error marcando leído: {e}")


class VentanaHistorial:
    """Lista de todos los mensajes recibidos, con filtro leído/no leído."""

    COLORES = {'notificacion': '#1B4F8A', 'instructivo': '#1D6A3A',
               'urgente': '#B71C1C', 'reunion': '#E65100'}

    def __init__(self, root: tk.Tk, bandeja_path, cola_mensajes: list, cola_lock):
        import json as _json

        self.root          = root
        self.bandeja_path  = bandeja_path
        self.cola_mensajes = cola_mensajes
        self.cola_lock     = cola_lock
        self.filtro        = 'todos'   # 'todos' | 'no_leido' | 'leido'
        self.mensajes      = []
        try:
            if Path(bandeja_path).exists():
                self.mensajes = _json.loads(
                    Path(bandeja_path).read_text(encoding='utf-8')
                )
        except Exception:
            pass

        self.win = tk.Toplevel(root)
        self.win.title('infoDesk — Mensajes recibidos')
        self.win.overrideredirect(True)
        self.win.attributes('-topmost', True)
        self.win.configure(bg='#FFFFFF')

        w, h = 580, 540
        self.win.update_idletasks()
        sw = self.win.winfo_screenwidth() or 1920
        sh = self.win.winfo_screenheight() or 1080
        self.win.geometry(f'{w}x{h}+{max(0,(sw-w)//2)}+{max(0,(sh-h)//2)}')

        # ── Header ────────────────────────────────────────────────────────────
        header = tk.Frame(self.win, bg='#1B4F8A', height=55)
        header.pack(fill='x')
        header.pack_propagate(False)

        if PIL_OK:
            ico_path = _get_resource_path('icono.ico')
            try:
                ico_img   = Image.open(str(ico_path)).resize((22, 22), Image.LANCZOS)
                ico_photo = ImageTk.PhotoImage(ico_img)
                self._ico_ref = ico_photo
                tk.Label(header, image=ico_photo, bg='#1B4F8A').pack(side='left', padx=(14, 4), pady=16)
            except Exception:
                pass

        no_leidos = sum(1 for m in self.mensajes if not m.get('_leido', False))
        titulo_hdr = 'Bandeja de mensajes'
        if no_leidos:
            titulo_hdr += f'  ·  {no_leidos} sin leer'
        tk.Label(header, text=titulo_hdr,
                 bg='#1B4F8A', fg='white',
                 font=('Segoe UI', 10, 'bold')).pack(side='left', pady=16)

        tk.Button(header, text='✕', bg='#1B4F8A', fg='white',
                  font=('Segoe UI', 12), relief='flat', cursor='hand2',
                  command=self.win.destroy).pack(side='right', padx=12)

        # ── Barra de filtros (ojo) ────────────────────────────────────────────
        barra = tk.Frame(self.win, bg='#E8EEF6', pady=6)
        barra.pack(fill='x', padx=10)

        self._btns_filtro = {}
        filtros = [
            ('todos',    '👁  Todos'),
            ('no_leido', '🔴  No leídos'),
            ('leido',    '✓  Leídos'),
        ]
        for key, label in filtros:
            btn = tk.Button(
                barra, text=label,
                font=('Segoe UI', 9), relief='flat', cursor='hand2',
                padx=12, pady=4,
                command=lambda k=key: self._aplicar_filtro(k),
            )
            btn.pack(side='left', padx=4)
            self._btns_filtro[key] = btn

        # ── Área de lista (canvas + scroll) ───────────────────────────────────
        frame_lista = tk.Frame(self.win, bg='#F0F4F8')
        frame_lista.pack(fill='both', expand=True, padx=10, pady=(6, 10))

        self.canvas = tk.Canvas(frame_lista, bg='#F0F4F8', highlightthickness=0)
        scroll = tk.Scrollbar(frame_lista, orient='vertical', command=self.canvas.yview)
        self.canvas.configure(yscrollcommand=scroll.set)
        scroll.pack(side='right', fill='y')
        self.canvas.pack(side='left', fill='both', expand=True)

        self.canvas.bind('<MouseWheel>', lambda e: self.canvas.yview_scroll(
            int(-1 * (e.delta / 120)), 'units'))

        self.frame_items = None
        self._renderizar()

        self.win.lift()
        self.win.focus_force()

    def _aplicar_filtro(self, filtro: str):
        self.filtro = filtro
        self._renderizar()

    def _renderizar(self):
        """Limpia y redibuja la lista según el filtro activo."""
        # Actualizar estilo botones
        estilos = {
            'todos':    ('#1B4F8A', 'white'),
            'no_leido': ('#E53935', 'white'),
            'leido':    ('#757575', 'white'),
        }
        inactivo_bg = '#D0DAF0'
        for key, btn in self._btns_filtro.items():
            if key == self.filtro:
                bg, fg = estilos[key]
                btn.configure(bg=bg, fg=fg)
            else:
                btn.configure(bg=inactivo_bg, fg='#333333')

        # Destruir lista anterior
        if self.frame_items is not None:
            self.frame_items.destroy()

        self.frame_items = tk.Frame(self.canvas, bg='#F0F4F8')
        win_id = self.canvas.create_window((0, 0), window=self.frame_items, anchor='nw')

        self.frame_items.bind('<Configure>', lambda e: self.canvas.configure(
            scrollregion=self.canvas.bbox('all')))
        self.canvas.bind('<Configure>', lambda e: self.canvas.itemconfig(win_id, width=e.width))

        # Filtrar mensajes
        if self.filtro == 'no_leido':
            lista = [m for m in self.mensajes if not m.get('_leido', False)]
        elif self.filtro == 'leido':
            lista = [m for m in self.mensajes if m.get('_leido', False)]
        else:
            lista = self.mensajes

        if not lista:
            texto = 'Sin mensajes recibidos aún.' if not self.mensajes else 'Sin mensajes en este filtro.'
            tk.Label(self.frame_items, text=texto,
                     bg='#F0F4F8', fg='#999', font=('Segoe UI', 10)
                     ).pack(pady=30)
            return

        for m in lista:
            leido   = m.get('_leido', False)
            color   = self.COLORES.get(m.get('tipo', ''), '#1B4F8A')
            card_bg = '#FFFFFF' if leido else '#EEF4FF'

            card = tk.Frame(self.frame_items, bg=card_bg, relief='flat', bd=0)
            card.pack(fill='x', padx=4, pady=3)

            tk.Frame(card, bg=color, width=5).pack(side='left', fill='y')

            info = tk.Frame(card, bg=card_bg, padx=10, pady=8)
            info.pack(side='left', fill='both', expand=True)

            top_row = tk.Frame(info, bg=card_bg)
            top_row.pack(fill='x')

            titulo_font = ('Segoe UI', 10, 'bold') if not leido else ('Segoe UI', 10)
            titulo_fg   = '#0A2A6E' if not leido else '#1A1A2E'
            tk.Label(top_row, text=m.get('titulo', ''),
                     bg=card_bg, fg=titulo_fg, font=titulo_font, anchor='w').pack(side='left')

            tk.Label(top_row,
                     text=m.get('_fecha_local', m.get('timestamp', '')),
                     bg=card_bg, fg='#999', font=('Segoe UI', 8)).pack(side='right')

            tk.Label(info,
                     text=m.get('cuerpo', '')[:80] + ('...' if len(m.get('cuerpo', '')) > 80 else ''),
                     bg=card_bg, fg='#555', font=('Segoe UI', 9),
                     anchor='w', wraplength=420, justify='left').pack(fill='x')

            badges = tk.Frame(info, bg=card_bg)
            badges.pack(anchor='w', pady=(3, 0))

            tk.Label(badges, text=f"  {m.get('tipo', '').upper()}  ",
                     bg=color, fg='white', font=('Segoe UI', 7, 'bold')).pack(side='left')

            if not leido:
                tk.Label(badges, text='  NO LEÍDO  ',
                         bg='#E53935', fg='white',
                         font=('Segoe UI', 7, 'bold')).pack(side='left', padx=(4, 0))
            else:
                tk.Label(badges, text='  LEÍDO  ',
                         bg='#9E9E9E', fg='white',
                         font=('Segoe UI', 7)).pack(side='left', padx=(4, 0))

            def _abrir(datos=m):
                mid = datos.get('mensaje_id')
                if mid:
                    _marcar_leido_en_bandeja(self.bandeja_path, mid)
                self.win.destroy()
                with self.cola_lock:
                    self.cola_mensajes.append(datos)

            # Botón ojo individual — columna derecha centrada
            col_der = tk.Frame(card, bg=card_bg, width=52)
            col_der.pack(side='right', fill='y')
            col_der.pack_propagate(False)

            btn_ver = tk.Button(
                col_der, text='👁',
                bg=color, fg='white',
                font=('Segoe UI', 11), relief='flat', cursor='hand2',
                width=3, pady=4,
                command=lambda d=m: _abrir(d),
            )
            btn_ver.place(relx=0.5, rely=0.5, anchor='center')


# ─── Loop principal de ventanas ────────────────────────────────────────────────
def iniciar_ventana_loop(cola_mensajes: list, cola_lock,
                          pc_nombre: str, api_url: str,
                          enviar_confirmacion_fn, tray_fn,
                          cola_bandeja=None, cola_bandeja_lock=None,
                          bandeja_path=None, **kwargs):
    """
    Inicia tkinter en el hilo principal y procesa la cola de mensajes.
    El icono de bandeja se lanza en un hilo aparte.
    """
    root = tk.Tk()
    root.withdraw()  # Ventana raíz invisible

    # Iniciar tray en hilo separado
    hilo_tray = threading.Thread(target=tray_fn, daemon=True)
    hilo_tray.start()

    ventana_activa = [False]

    def verificar_cola():
        if not ventana_activa[0]:
            with cola_lock:
                if cola_mensajes:
                    datos = cola_mensajes.pop(0)
                else:
                    datos = None
            if datos:
                ventana_activa[0] = True

                def al_cerrar():
                    ventana_activa[0] = False

                try:
                    VentanaMensaje(
                        root=root,
                        datos=datos,
                        pc_nombre=pc_nombre,
                        api_url=api_url,
                        enviar_confirmacion_fn=enviar_confirmacion_fn,
                        al_cerrar_fn=al_cerrar,
                    )
                except Exception as e:
                    log.error(f"Error al crear ventana emergente: {e}", exc_info=True)
                    ventana_activa[0] = False  # Resetear para que el próximo mensaje funcione
        root.after(500, verificar_cola)

    def verificar_bandeja():
        if cola_bandeja is not None and cola_bandeja_lock is not None:
            with cola_bandeja_lock:
                abrir = bool(cola_bandeja)
                cola_bandeja.clear()
            if abrir and bandeja_path is not None:
                VentanaHistorial(root, bandeja_path, cola_mensajes, cola_lock)
        root.after(600, verificar_bandeja)

    root.after(600, verificar_bandeja)
    root.after(500, verificar_cola)
    root.mainloop()
