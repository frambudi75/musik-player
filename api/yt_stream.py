import sys
import os
import json
import re

# Ensure UTF-8 output encoding on Windows
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def get_stream_url(video_id_or_url):
    import yt_dlp

    video_id_or_url = video_id_or_url.strip()
    if not video_id_or_url:
        return {'status': 'error', 'message': 'ID Video atau URL kosong'}

    if not video_id_or_url.startswith('http'):
        url = f"https://www.youtube.com/watch?v={video_id_or_url}"
    else:
        url = video_id_or_url

    ydl_opts = {
        'format': 'bestaudio[ext=m4a]/bestaudio/best',
        'quiet': True,
        'no_warnings': True,
        'noplaylist': True,
    }

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            if not info:
                return {'status': 'error', 'message': 'Tidak dapat memuat stream audio.'}

            stream_url = info.get('url')
            title = info.get('title') or 'Unknown Title'
            uploader = info.get('uploader') or info.get('channel') or 'Unknown Artist'
            v_id = info.get('id') or ''
            duration = info.get('duration') or 0

            cover = f"https://i.ytimg.com/vi/{v_id}/hqdefault.jpg" if v_id else ""

            return {
                'status': 'success',
                'stream_url': stream_url,
                'id': v_id,
                'title': title,
                'artist': uploader,
                'duration': duration,
                'cover': cover
            }
    except Exception as e:
        return {'status': 'error', 'message': str(e)}

if __name__ == '__main__':
    target = sys.argv[1] if len(sys.argv) > 1 else 'yKNxeF4KMsY'
    res = get_stream_url(target)
    print(json.dumps(res, ensure_ascii=False))
