# infoDesk — Cliente de escritorio para Windows
# Conecta al servidor Reverb (protocolo Pusher) y muestra notificaciones emergentes.

import configparser
import json
import logging
import os
import socket
import sys
import threading
import time
from pathlib import Path

import requests
import websocket

# Importaciones con manejo de error para compatibilidad con PyInstaller
try:
    import pystray
    from PIL import Image, ImageDraw
    TRAY_DISPONIBLE = True
except ImportError:
    TRAY_DISPONIBLE = False

# ─── Rutas ─────────────────────────────────────────────────────────────────────
if getattr(sys, 'frozen', False):
    BASE_DIR = Path(sys.executable).parent
else:
    BASE_DIR = Path(__file__).parent

CONFIG_PATH = BASE_DIR / 'config.ini'
LOG_PATH        = BASE_DIR / 'mensadesk.log'
BANDEJA_PATH    = BASE_DIR / 'bandeja.json'

# ─── Logging ───────────────────────────────────────────────────────────────────
logging.basicConfig(
    filename=str(LOG_PATH),
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S',
)
log = logging.getLogger('mensadesk')

# ─── Configuración ─────────────────────────────────────────────────────────────
config = configparser.ConfigParser()
# Leer config externo (dist/config.ini) — editable sin recompilar
if CONFIG_PATH.exists():
    config.read(str(CONFIG_PATH), encoding='utf-8-sig')  # utf-8-sig maneja BOM
# Si no existe o está vacío, leer el bundleado dentro del exe
if not config.sections() and getattr(sys, 'frozen', False):
    _bundle_cfg = Path(sys._MEIPASS) / 'config.ini'
    if _bundle_cfg.exists():
        config.read(str(_bundle_cfg), encoding='utf-8')

SERVIDOR_HOST    = config.get('servidor', 'host',    fallback='localhost')
SERVIDOR_PORT    = config.get('servidor', 'port',    fallback='8080')
SERVIDOR_APP_KEY = config.get('servidor', 'app_key', fallback='')
SERVIDOR_SCHEME  = config.get('servidor', 'scheme',  fallback='ws')
API_URL          = config.get('servidor', 'api_url', fallback='http://localhost:8000').rstrip('/')

def _leer_nombre_config(path: Path) -> str:
    """Lee nombre= del config.ini directamente, sin depender de configparser."""
    for p in [path]:
        try:
            texto = p.read_text(encoding='utf-8-sig')
            en_pc = False
            for linea in texto.splitlines():
                l = linea.strip()
                if l.lower() == '[pc]':
                    en_pc = True
                    continue
                if en_pc:
                    if l.startswith('['):
                        break
                    if l.startswith(';') or l.startswith('#') or not l:
                        continue
                    if '=' in l:
                        clave, _, valor = l.partition('=')
                        if clave.strip().lower() == 'nombre':
                            v = valor.strip()
                            if v:
                                return v
        except Exception:
            pass
    return ''

PC_NOMBRE = _leer_nombre_config(CONFIG_PATH)
# Fallback al bundleado dentro del exe
if not PC_NOMBRE and getattr(sys, 'frozen', False):
    _bundle = Path(sys._MEIPASS) / 'config.ini'
    PC_NOMBRE = _leer_nombre_config(_bundle)
if not PC_NOMBRE:
    PC_NOMBRE = socket.gethostname()
log.info(f"Nombre PC resuelto: '{PC_NOMBRE}' (config: {CONFIG_PATH})")

WS_URL = f"{SERVIDOR_SCHEME}://{SERVIDOR_HOST}:{SERVIDOR_PORT}/app/{SERVIDOR_APP_KEY}"

# ─── Estado global ─────────────────────────────────────────────────────────────
_ultimo_mensaje  = None
_cola_mensajes   = []
_cola_lock       = threading.Lock()
_ventana_abierta = False
_ws_conn         = None
_tray_icon       = None
_app_corriendo   = True

log.info(f"infoDesk iniciado | PC: {PC_NOMBRE} | WS: {WS_URL}")


# ─── Registro en el servidor ───────────────────────────────────────────────────
def registrar_pc():
    """Notifica al servidor que esta PC está activa (heartbeat)."""
    try:
        ip = socket.gethostbyname(socket.gethostname())
        requests.post(
            f"{API_URL}/api/registro",
            json={'nombre': PC_NOMBRE, 'ip': ip},
            timeout=5,
        )
        log.info(f"Registrado en servidor: {PC_NOMBRE} ({ip})")
    except Exception as e:
        log.warning(f"Error al registrar PC: {e}")


def heartbeat_loop():
    """Envía heartbeat cada 30 segundos para mantener la PC como activa."""
    while _app_corriendo:
        registrar_pc()
        time.sleep(30)


# ─── Confirmación de lectura ───────────────────────────────────────────────────
def enviar_confirmacion(mensaje_id: int, accion: str):
    """Envía confirmación al servidor (recibido | visto | descargado)."""
    try:
        ip = socket.gethostbyname(socket.gethostname())
        requests.post(
            f"{API_URL}/api/confirmacion",
            json={
                'mensaje_id': mensaje_id,
                'pc_nombre':  PC_NOMBRE,
                'pc_ip':      ip,
                'accion':     accion,
            },
            timeout=5,
        )
        log.info(f"Confirmación enviada: mensaje #{mensaje_id} — {accion}")
    except Exception as e:
        log.warning(f"Error al enviar confirmación: {e}")


# ─── WebSocket Pusher Protocol ─────────────────────────────────────────────────
def on_open(ws):
    log.info("WebSocket conectado")


def on_message(ws, message):
    global _ultimo_mensaje
    try:
        payload = json.loads(message)
        evento  = payload.get('event', '')

        if evento == 'pusher:connection_established':
            # Suscribirse al canal público de mensajes
            ws.send(json.dumps({
                'event': 'pusher:subscribe',
                'data':  {'channel': 'mensajes'},
            }))
            log.info("Suscrito al canal 'mensajes'")
            registrar_pc()

        elif evento == 'pusher:ping':
            ws.send(json.dumps({'event': 'pusher:pong', 'data': {}}))

        elif evento == 'mensaje.enviado':
            # data es un JSON string (doble codificación Pusher)
            raw = payload.get('data', '{}')
            datos = json.loads(raw) if isinstance(raw, str) else raw
            log.info(f"Mensaje recibido: {datos.get('titulo', '')}")

            _ultimo_mensaje = datos
            mostrar_notificacion_thread(datos)

    except Exception as e:
        log.error(f"Error al procesar mensaje: {e}")


def on_error(ws, error):
    log.error(f"WebSocket error: {error}")


def on_close(ws, code, msg):
    log.info(f"WebSocket desconectado (código {code})")


def iniciar_websocket():
    """Inicia la conexión WebSocket con reconexión automática."""
    global _ws_conn, _app_corriendo
    while _app_corriendo:
        try:
            log.info(f"Conectando a {WS_URL}...")
            ws = websocket.WebSocketApp(
                WS_URL,
                on_open=on_open,
                on_message=on_message,
                on_error=on_error,
                on_close=on_close,
            )
            _ws_conn = ws
            ws.run_forever(ping_interval=30, ping_timeout=10)
        except Exception as e:
            log.error(f"Error al conectar WebSocket: {e}")

        if _app_corriendo:
            log.info("Reintentando conexión en 10 segundos...")
            time.sleep(10)


# ─── Mostrar notificación en hilo separado ─────────────────────────────────────
def guardar_en_bandeja(datos: dict):
    """Guarda el mensaje recibido en bandeja.json (máx 100 mensajes)."""
    try:
        import datetime, json as _json
        bandeja = []
        if BANDEJA_PATH.exists():
            try:
                bandeja = _json.loads(BANDEJA_PATH.read_text(encoding='utf-8'))
            except Exception:
                bandeja = []
        entrada = dict(datos)
        entrada['_fecha_local'] = datetime.datetime.now().strftime('%d/%m/%Y %H:%M')
        entrada.setdefault('_leido', False)
        bandeja.insert(0, entrada)
        bandeja = bandeja[:100]  # Máximo 100 mensajes
        utf8 = __import__('codecs').lookup('utf-8').incrementaldecoder
        BANDEJA_PATH.write_text(
            _json.dumps(bandeja, ensure_ascii=False, indent=2),
            encoding='utf-8'
        )
    except Exception as e:
        log.warning(f"Error guardando en bandeja: {e}")


def mostrar_notificacion_thread(datos: dict):
    """Lanza la ventana emergente en el hilo principal de tkinter."""
    guardar_en_bandeja(datos)
    with _cola_lock:
        _cola_mensajes.append(datos)
    # La ventana.py gestiona la cola desde su propio loop


_cola_bandeja      = []
_cola_bandeja_lock = threading.Lock()


def mostrar_ultimo_mensaje():
    """Re-abre la ventana con el último mensaje recibido."""
    if _ultimo_mensaje:
        with _cola_lock:
            _cola_mensajes.append(_ultimo_mensaje)


def abrir_bandeja():
    """Señaliza al loop tkinter que abra la ventana de historial."""
    with _cola_bandeja_lock:
        _cola_bandeja.append(True)


# ─── Icono en bandeja del sistema ─────────────────────────────────────────────
def crear_icono_imagen():
    """Genera un icono simple si no existe icono.ico."""
    img = Image.new('RGBA', (64, 64), color=(27, 79, 138, 255))
    d   = ImageDraw.Draw(img)
    d.ellipse([8, 8, 56, 56], fill=(255, 255, 255, 200))
    return img


def abrir_config():
    os.startfile(str(CONFIG_PATH))


def salir_app(icon, item=None):
    global _app_corriendo
    _app_corriendo = False
    if _ws_conn:
        try:
            _ws_conn.close()
        except Exception:
            pass
    if icon:
        icon.stop()
    os._exit(0)


def _get_resource_path(filename: str) -> Path:
    """Busca archivo junto al exe primero, luego en el bundle PyInstaller."""
    externo = BASE_DIR / filename
    if externo.exists():
        return externo
    if getattr(sys, 'frozen', False):
        return Path(sys._MEIPASS) / filename
    return BASE_DIR / filename


def iniciar_tray():
    global _tray_icon
    if not TRAY_DISPONIBLE:
        return

    ico_path = _get_resource_path('icono.ico')
    if ico_path.exists():
        img = Image.open(str(ico_path))
    else:
        img = crear_icono_imagen()

    menu = pystray.Menu(
        pystray.MenuItem('Ver último mensaje',    lambda icon, item: mostrar_ultimo_mensaje()),
        pystray.MenuItem('Bandeja de mensajes',   lambda icon, item: abrir_bandeja()),
        pystray.Menu.SEPARATOR,
        pystray.MenuItem('Salir',                 salir_app),
    )
    _tray_icon = pystray.Icon('infoDesk', img, 'infoDesk', menu)
    _tray_icon.run()


# ─── Inicio automático con Windows ────────────────────────────────────────────
def registrar_inicio_windows():
    """Agrega infoDesk al inicio automático de Windows vía registro."""
    if not getattr(sys, 'frozen', False):
        return  # Solo aplicar cuando es .exe compilado
    try:
        import winreg
        ruta_exe = sys.executable
        clave = winreg.OpenKey(
            winreg.HKEY_CURRENT_USER,
            r'Software\Microsoft\Windows\CurrentVersion\Run',
            0,
            winreg.KEY_SET_VALUE,
        )
        winreg.SetValueEx(clave, 'infoDesk', 0, winreg.REG_SZ, ruta_exe)
        winreg.CloseKey(clave)
        log.info("Registrado en inicio automático de Windows")
    except Exception as e:
        log.warning(f"No se pudo registrar en inicio automático: {e}")


# ─── Punto de entrada ─────────────────────────────────────────────────────────
if __name__ == '__main__':
    registrar_inicio_windows()

    # Hilo WebSocket
    hilo_ws = threading.Thread(target=iniciar_websocket, daemon=True)
    hilo_ws.start()

    # Hilo heartbeat
    hilo_hb = threading.Thread(target=heartbeat_loop, daemon=True)
    hilo_hb.start()

    # Importar ventana aquí para que tkinter se inicie en el hilo principal
    from ventana import iniciar_ventana_loop
    iniciar_ventana_loop(
        cola_mensajes=_cola_mensajes,
        cola_lock=_cola_lock,
        cola_bandeja=_cola_bandeja,
        cola_bandeja_lock=_cola_bandeja_lock,
        bandeja_path=BANDEJA_PATH,
        pc_nombre=PC_NOMBRE,
        api_url=API_URL,
        enviar_confirmacion_fn=enviar_confirmacion,
        tray_fn=iniciar_tray,
    )
