import sys
import os
import json
import re

# Ensure UTF-8 encoding on Windows
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
        for p in ['/usr/bin', '/usr/local/bin', '/bin']:
            if os.path.exists(os.path.join(p, 'ffmpeg')):
                return p
        return ''
    for p in FFMPEG_PATHS:
        if os.path.exists(os.path.join(p, 'ffmpeg.exe')):
            return p
    return ''

def search_youtube(query, limit=10):
    import yt_dlp
    
    is_url = bool(re.match(r'^(https?://)?(www\.)?(youtube\.com|youtu\.be)/.+$', query, re.I))
    target = query if is_url else f'ytsearch{limit}:{query}'

    ydl_opts = {
        'extract_flat': True,
        'skip_download': True,
        'quiet': True,
        'no_warnings': True,
    }

    results = []
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(target, download=False)
            if not info:
                return {'status': 'error', 'message': 'Pencarian tidak menemukan hasil.'}

            entries = info.get('entries', []) if 'entries' in info else [info]
            for entry in entries:
                if not entry or not entry.get('id'):
                    continue
                
                vid_id = entry.get('id')
                title = entry.get('title', 'Unknown Title')
                uploader = entry.get('uploader') or entry.get('channel') or 'Unknown Artist'
                duration = entry.get('duration') or 0
                thumbnails = entry.get('thumbnails') or []
                
                # Pick best thumbnail
                thumb_url = f'https://i.ytimg.com/vi/{vid_id}/hqdefault.jpg'
                if thumbnails and isinstance(thumbnails, list):
                    thumb_url = thumbnails[-1].get('url', thumb_url)

                results.append({
                    'id': vid_id,
                    'title': title,
                    'artist': uploader,
                    'duration': duration,
                    'thumbnail': thumb_url,
                    'url': f'https://www.youtube.com/watch?v={vid_id}'
                })

        return {'status': 'success', 'results': results}
    except Exception as e:
        return {'status': 'error', 'message': str(e)}

def get_stream_url(video_id_or_url):
    import yt_dlp
    
    if not video_id_or_url.startswith('http'):
        target = f'https://www.youtube.com/watch?v={video_id_or_url}'
    else:
        target = video_id_or_url

    ffmpeg_dir = get_ffmpeg_dir()
    ydl_opts = {
        'format': 'bestaudio[ext=m4a]/bestaudio/best',
        'skip_download': True,
        'quiet': True,
        'no_warnings': True,
    }
    if ffmpeg_dir:
        ydl_opts['ffmpeg_location'] = ffmpeg_dir

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(target, download=False)
            if not info:
                return {'status': 'error', 'message': 'Tidak dapat mengekstrak stream audio.'}
            
            stream_url = info.get('url')
            # If manifest or dash, search formats
            if not stream_url and 'formats' in info:
                audio_formats = [f for f in info['formats'] if f.get('acodec') != 'none' and f.get('url')]
                if audio_formats:
                    # Sort by audio bitrate
                    audio_formats.sort(key=lambda x: x.get('abr') or 0, reverse=True)
                    stream_url = audio_formats[0].get('url')

            if not stream_url:
                return {'status': 'error', 'message': 'Audio stream URL tidak ditemukan.'}

            return {
                'status': 'success',
                'id': info.get('id'),
                'title': info.get('title'),
                'artist': info.get('uploader') or info.get('channel') or 'Unknown Artist',
                'duration': info.get('duration') or 0,
                'stream_url': stream_url,
                'ext': info.get('ext') or 'm4a',
                'thumbnail': info.get('thumbnail') or f'https://i.ytimg.com/vi/{info.get("id")}/hqdefault.jpg'
            }
    except Exception as e:
        return {'status': 'error', 'message': str(e)}

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print(json.dumps({'status': 'error', 'message': 'Argumen tidak valid. Gunakan: stream_extractor.py [search|stream] [query|id] [limit]'}))
        sys.exit(1)

    action = sys.argv[1].lower()
    param = sys.argv[2]
    
    if action == 'search':
        limit = int(sys.argv[3]) if len(sys.argv) > 3 else 10
        out = search_youtube(param, limit)
        print(json.dumps(out, ensure_ascii=False))
    elif action == 'stream':
        out = get_stream_url(param)
        print(json.dumps(out, ensure_ascii=False))
    else:
        print(json.dumps({'status': 'error', 'message': f'Aksi {action} tidak dikenali.'}))
