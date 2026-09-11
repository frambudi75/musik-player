import sys
import os
import shutil
import subprocess
import urllib.request

# Ensure binary mode on Windows stdout
if sys.platform == 'win32':
    import msvcrt
    msvcrt.setmode(sys.stdout.fileno(), os.O_BINARY)

FFMPEG_PATHS = [
    r'C:\ffmpeg\bin\ffmpeg.exe',
    r'C:\Program Files\ffmpeg\bin\ffmpeg.exe',
    '/usr/bin/ffmpeg',
    '/usr/local/bin/ffmpeg',
    '/bin/ffmpeg',
    '/snap/bin/ffmpeg',
    '/www/server/ffmpeg/bin/ffmpeg',
    '/root/bin/ffmpeg'
]

def get_ffmpeg_bin():
    p = shutil.which('ffmpeg')
    if p:
        return p
    for path in FFMPEG_PATHS:
        if os.path.exists(path) and (os.access(path, os.X_OK) if sys.platform != 'win32' else True):
            return path
    return None

def stream_audio(video_id):
    import yt_dlp
    
    url = f"https://www.youtube.com/watch?v={video_id}" if not video_id.startswith('http') else video_id
    
    ydl_opts = {
        'format': 'bestaudio/best',
        'quiet': True,
        'no_warnings': True,
        'noplaylist': True,
    }
    
    stream_url = None
    http_headers = {}
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            stream_url = info.get('url')
            http_headers = info.get('http_headers', {})
            if not stream_url:
                sys.exit(1)
    except Exception:
        sys.exit(1)

    ffmpeg_bin = get_ffmpeg_bin()

    if ffmpeg_bin:
        # Transcode in real-time to progressive standard MP3
        cmd = [
            ffmpeg_bin,
            '-reconnect', '1',
            '-reconnect_streamed', '1',
            '-reconnect_delay_max', '5',
            '-i', stream_url,
            '-vn',
            '-c:a', 'libmp3lame',
            '-b:a', '192k',
            '-f', 'mp3',
            '-'
        ]
        try:
            proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL)
            while True:
                chunk = proc.stdout.read(8192)
                if not chunk:
                    break
                sys.stdout.buffer.write(chunk)
                sys.stdout.buffer.flush()
            proc.stdout.close()
            proc.terminate()
        except (BrokenPipeError, IOError):
            pass
        except Exception:
            pass
    else:
        # Direct stream fallback without ffmpeg
        try:
            req = urllib.request.Request(
                stream_url,
                headers=http_headers or {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                }
            )
            with urllib.request.urlopen(req, timeout=30) as resp:
                while True:
                    chunk = resp.read(8192)
                    if not chunk:
                        break
                    sys.stdout.buffer.write(chunk)
                    sys.stdout.buffer.flush()
        except (BrokenPipeError, IOError):
            pass
        except Exception:
            pass

if __name__ == '__main__':
    v_id = sys.argv[1] if len(sys.argv) > 1 else 'B3g6Ol3aP78'
    stream_audio(v_id)
