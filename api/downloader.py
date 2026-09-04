import sys
import os
import json
import re
import subprocess

# Ensure UTF-8 output encoding on Windows
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

FFMPEG_PATHS = [
    r'C:\Users\habib\AppData\Local\Microsoft\WinGet\Packages\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\ffmpeg-8.1.1-full_build\bin',
    r'C:\ffmpeg\bin',
    r'C:\Program Files\ffmpeg\bin',
    '/usr/bin',
    '/usr/local/bin'
]

def get_ffmpeg_dir():
    if sys.platform != 'win32':
        # On Linux/aaPanel, check if ffmpeg is in PATH
        for p in ['/usr/bin', '/usr/local/bin', '/bin']:
            if os.path.exists(os.path.join(p, 'ffmpeg')):
                return p
        return ''
    for p in FFMPEG_PATHS:
        if os.path.exists(os.path.join(p, 'ffmpeg.exe')):
            return p
    return ''

def normalize_str(s):
    if not s:
        return ""
    # Strip special chars and lowercase
    return re.sub(r'[\W_]+', '', s.lower())

def find_existing_song(title, video_id, output_dir):
    if not os.path.exists(output_dir):
        return None

    norm_title = normalize_str(title)
    video_id = video_id.strip() if video_id else ""

    allowed_exts = {'.mp3', '.flac', '.wav', '.ogg', '.m4a', '.aac', '.opus'}
    for f in os.listdir(output_dir):
        base, ext = os.path.splitext(f)
        if ext.lower() in allowed_exts:
            # Check exact or normalized filename match
            norm_f = normalize_str(base)
            if video_id and f"[{video_id}]" in f:
                return f
            if norm_title and (norm_title == norm_f or norm_title in norm_f or norm_f in norm_title):
                # High confidence match if lengths are relatively close or exact
                if abs(len(norm_title) - len(norm_f)) < 10 or norm_title == norm_f:
                    return f
    return None

def download_youtube(query, output_dir):
    import yt_dlp
    
    ffmpeg_dir = get_ffmpeg_dir()
    
    # Check if query is URL or search
    is_url = bool(re.match(r'^(https?://)?(www\.)?(youtube\.com|youtu\.be)/.+$', query, re.I))
    target = query if is_url else f'ytsearch1:{query}'

    outtmpl = os.path.join(output_dir, '%(title)s.%(ext)s')

    ydl_opts_info = {
        'noplaylist': True,
        'quiet': True,
        'no_warnings': True,
        'windowsfilenames': True,
    }
    if ffmpeg_dir:
        ydl_opts_info['ffmpeg_location'] = ffmpeg_dir

    # Extract metadata first to check if file already exists
    with yt_dlp.YoutubeDL(ydl_opts_info) as ydl_info:
        info = ydl_info.extract_info(target, download=False)
        if not info:
            return {'status': 'error', 'message': 'Tidak dapat menemukan media dari link atau query tersebut.'}
        
        if 'entries' in info and info['entries']:
            video_info = info['entries'][0]
        else:
            video_info = info

        title = video_info.get('title', 'Unknown Title')
        uploader = video_info.get('uploader') or video_info.get('channel') or 'Unknown Artist'
        video_id = video_info.get('id', '')
        
        # Check if already in output_dir
        expected_raw = ydl_info.prepare_filename(video_info)
        expected_mp3 = os.path.splitext(expected_raw)[0] + '.mp3'
        
        if os.path.exists(expected_mp3):
            return {
                'status': 'already_exists',
                'message': f'Lagu "{title}" sudah ada di koleksi Anda!',
                'title': title,
                'artist': uploader,
                'filename': os.path.basename(expected_mp3)
            }

        existing_match = find_existing_song(title, video_id, output_dir)
        if existing_match:
            return {
                'status': 'already_exists',
                'message': f'Lagu "{title}" sudah ada di koleksi Anda!',
                'title': title,
                'artist': uploader,
                'filename': existing_match
            }

    # Not existing: Proceed to download
    ydl_opts_download = {
        'format': 'bestaudio/best',
        'outtmpl': outtmpl,
        'postprocessors': [
            {
                'key': 'FFmpegExtractAudio',
                'preferredcodec': 'mp3',
                'preferredquality': '320',
            },
            {
                'key': 'FFmpegMetadata',
                'add_metadata': True,
            },
            {
                'key': 'EmbedThumbnail',
                'already_have_thumbnail': False,
            },
        ],
        'writethumbnail': True,
        'noplaylist': True,
        'quiet': False,
        'no_warnings': False,
        'windowsfilenames': True,
    }
    if ffmpeg_dir:
        ydl_opts_download['ffmpeg_location'] = ffmpeg_dir

    with yt_dlp.YoutubeDL(ydl_opts_download) as ydl:
        download_info = ydl.extract_info(video_info.get('webpage_url') or target, download=True)
        if 'entries' in download_info and download_info['entries']:
            final_info = download_info['entries'][0]
        else:
            final_info = download_info
            
        final_raw = ydl.prepare_filename(final_info)
        final_file = os.path.splitext(final_raw)[0] + '.mp3'
        
        return {
            'status': 'success',
            'message': 'Lagu berhasil didownload dan disimpan!',
            'title': title,
            'artist': uploader,
            'filename': os.path.basename(final_file)
        }

def download_spotify(url, output_dir):
    ffmpeg_dir = get_ffmpeg_dir()
    env = os.environ.copy()
    if ffmpeg_dir:
        env['PATH'] = f"{ffmpeg_dir};{env.get('PATH', '')}"

    cmd = [
        sys.executable,
        '-m',
        'spotdl',
        'download',
        url,
        '--output',
        output_dir,
        '--format',
        'mp3',
        '--bitrate',
        '320k',
        '--generate-lrc'
    ]

    res = subprocess.run(cmd, capture_output=True, text=True, env=env, encoding='utf-8')
    if res.returncode == 0 or 'Downloaded' in res.stdout or 'Found' in res.stdout:
        return {
            'status': 'success',
            'message': 'Lagu/Playlist Spotify berhasil diunduh!',
            'stdout': res.stdout
        }
    else:
        return {
            'status': 'error',
            'message': 'Gagal mengunduh dari Spotify',
            'stderr': res.stderr,
            'stdout': res.stdout
        }

def main():
    if len(sys.argv) < 3:
        print(json.dumps({'status': 'error', 'message': 'Argumen kurang: mode dan url/query'}))
        sys.exit(1)

    mode = sys.argv[1] # 'youtube' or 'spotify'
    target = sys.argv[2]
    
    songs_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'songs'))
    os.makedirs(songs_dir, exist_ok=True)

    try:
        if mode == 'spotify' or 'spotify.com' in target:
            result = download_spotify(target, songs_dir)
        else:
            result = download_youtube(target, songs_dir)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({'status': 'error', 'message': str(e)}))

if __name__ == '__main__':
    main()
