import sys
import os
import json
import re

# Ensure UTF-8 output encoding on Windows
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def search_youtube(query, limit=12):
    import yt_dlp
    
    query = query.strip()
    if not query:
        return {'status': 'error', 'message': 'Query pencarian kosong'}

    # Determine search target
    if re.match(r'^(https?://)?(www\.)?(youtube\.com|youtu\.be)/.+$', query, re.I):
        target = query
    else:
        target = f"ytsearch{limit}:{query}"

    ydl_opts = {
        'quiet': True,
        'extract_flat': True,
        'no_warnings': True,
        'noplaylist': False,
    }

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(target, download=False)
            if not info:
                return {'status': 'error', 'message': 'Tidak ada hasil ditemukan.'}

            entries = info.get('entries') or [info]
            results = []

            for entry in entries:
                if not entry:
                    continue
                v_id = entry.get('id')
                if not v_id:
                    continue
                
                title = entry.get('title') or 'Unknown Title'
                uploader = entry.get('uploader') or entry.get('channel') or 'Unknown Artist'
                duration_sec = entry.get('duration') or 0
                
                # Format duration mm:ss
                mins = int(duration_sec // 60)
                secs = int(duration_sec % 60)
                duration_str = f"{mins}:{secs:02d}" if duration_sec > 0 else "--:--"

                # Extract best thumbnail
                thumbnails = entry.get('thumbnails') or []
                thumbnail_url = f"https://i.ytimg.com/vi/{v_id}/hqdefault.jpg"
                if thumbnails:
                    thumbnail_url = thumbnails[-1].get('url') or thumbnail_url

                results.append({
                    'id': v_id,
                    'title': title,
                    'artist': uploader,
                    'duration': duration_str,
                    'duration_sec': duration_sec,
                    'cover': thumbnail_url,
                    'url': f"https://www.youtube.com/watch?v={v_id}"
                })

            return {'status': 'success', 'query': query, 'total': len(results), 'results': results}
    except Exception as e:
        return {'status': 'error', 'message': str(e)}

if __name__ == '__main__':
    q = sys.argv[1] if len(sys.argv) > 1 else 'Top Indonesia Hits'
    limit = int(sys.argv[2]) if len(sys.argv) > 2 else 12
    res = search_youtube(q, limit)
    print(json.dumps(res, ensure_ascii=False))
