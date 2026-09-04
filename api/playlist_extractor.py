import sys
import os
import json
import re
import ssl
import urllib.request
import yt_dlp

# Ensure UTF-8 output encoding on Windows
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def get_ssl_context():
    try:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        return ctx
    except Exception:
        return None

def extract_spotify_metadata(url):
    match = re.search(r'spotify\.com/(playlist|album|track)/([a-zA-Z0-9]+)', url)
    if not match:
        return {'status': 'error', 'message': 'Format link Spotify tidak valid.'}
    
    stype, sid = match.group(1), match.group(2)
    embed_url = f"https://open.spotify.com/embed/{stype}/{sid}"
    
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    }
    
    try:
        req = urllib.request.Request(embed_url, headers=headers)
        ctx = get_ssl_context()
        with urllib.request.urlopen(req, timeout=12, context=ctx) as resp:
            html = resp.read().decode('utf-8', errors='ignore')
            
            # Look for __NEXT_DATA__
            m = re.search(r'<script id="__NEXT_DATA__" type="application/json">(.+?)</script>', html)
            if m:
                data = json.loads(m.group(1))
                props = data.get('props', {}).get('pageProps', {}).get('state', {}).get('data', {}).get('entity', {})
                playlist_title = props.get('name') or props.get('title') or ('Spotify ' + stype.capitalize())
                cover_img = ''
                images = props.get('visualIdentity', {}).get('image', []) or props.get('images', [])
                if images and isinstance(images, list) and len(images) > 0:
                    cover_img = images[0].get('url', '')
                
                track_list = []
                if stype == 'track':
                    artists_list = [a.get('name', '') for a in props.get('artists', []) if a.get('name')]
                    artists = ', '.join(artists_list) or 'Unknown Artist'
                    artists = re.sub(r'\s+', ' ', artists.replace('\xa0', ' ')).strip()
                    track_name = re.sub(r'\s+', ' ', (props.get('name') or 'Unknown Track').replace('\xa0', ' ')).strip()
                    track_list.append({
                        'title': track_name,
                        'artist': artists,
                        'duration_ms': props.get('duration_ms', 0),
                        'query': f"{artists} - {track_name}"
                    })
                else:
                    track_list_raw = props.get('trackList', [])
                    for t in track_list_raw:
                        artists = t.get('subtitle', '') or 'Unknown Artist'
                        artists = re.sub(r'\s+', ' ', artists.replace('\xa0', ' ')).strip()
                        t_name = re.sub(r'\s+', ' ', (t.get('title') or '').replace('\xa0', ' ')).strip()
                        if t_name:
                            track_list.append({
                                'title': t_name,
                                'artist': artists,
                                'duration_ms': t.get('duration', 0),
                                'query': f"{artists} - {t_name}" if artists else t_name
                            })
                
                if track_list:
                    return {
                        'status': 'success',
                        'platform': 'spotify',
                        'playlist_name': playlist_title,
                        'cover': cover_img,
                        'total_tracks': len(track_list),
                        'tracks': track_list
                    }
    except Exception as e:
        return {'status': 'error', 'message': f'Gagal membaca data Spotify: {str(e)}'}

    # Fallback to yt-dlp flat extraction if embed scraping fails
    try:
        ydl_opts = {'extract_flat': True, 'quiet': True, 'no_warnings': True}
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            if info:
                title = info.get('title', 'Spotify Playlist')
                entries = info.get('entries', [])
                track_list = []
                for entry in entries:
                    if entry:
                        t_title = entry.get('title', '')
                        t_artist = entry.get('uploader') or entry.get('artist') or ''
                        if t_title:
                            track_list.append({
                                'title': t_title,
                                'artist': t_artist,
                                'query': f"{t_artist} - {t_title}" if t_artist else t_title
                            })
                if track_list:
                    return {
                        'status': 'success',
                        'platform': 'spotify',
                        'playlist_name': title,
                        'total_tracks': len(track_list),
                        'tracks': track_list
                    }
    except Exception as e:
        pass

    return {'status': 'error', 'message': 'Tidak dapat mengekstrak daftar lagu dari link Spotify tersebut.'}

def extract_youtube_metadata(url):
    ydl_opts = {
        'extract_flat': True,
        'quiet': True,
        'no_warnings': True,
    }
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            if not info:
                return {'status': 'error', 'message': 'Link YouTube tidak valid atau tidak dapat diakses.'}
            
            if 'entries' in info and info['entries'] is not None:
                title = info.get('title') or 'YouTube Playlist'
                playlist_cover = ''
                if info.get('thumbnails') and isinstance(info.get('thumbnails'), list) and len(info.get('thumbnails')) > 0:
                    playlist_cover = info['thumbnails'][-1].get('url', '')

                tracks = []
                for entry in info['entries']:
                    if entry:
                        t_title = entry.get('title') or ''
                        if not t_title or t_title in ['[Deleted video]', '[Private video]', 'Unknown Title']:
                            continue
                        t_uploader = entry.get('uploader') or entry.get('channel') or 'Unknown Artist'
                        v_id = entry.get('id')
                        v_url = entry.get('url')
                        if not v_url or not v_url.startswith('http'):
                            v_url = f"https://www.youtube.com/watch?v={v_id}" if v_id else f"{t_uploader} - {t_title}"
                        
                        if not playlist_cover and entry.get('thumbnails'):
                            playlist_cover = entry['thumbnails'][-1].get('url', '')

                        tracks.append({
                            'title': t_title,
                            'artist': t_uploader,
                            'query': v_url,
                            'duration': entry.get('duration', 0)
                        })
                return {
                    'status': 'success',
                    'platform': 'youtube',
                    'playlist_name': title,
                    'cover': playlist_cover,
                    'total_tracks': len(tracks),
                    'tracks': tracks
                }
            else:
                # Single track
                t_title = info.get('title') or 'Unknown Title'
                t_uploader = info.get('uploader') or info.get('channel') or 'Unknown Artist'
                return {
                    'status': 'success',
                    'platform': 'youtube_single',
                    'playlist_name': t_title,
                    'total_tracks': 1,
                    'tracks': [{
                        'title': t_title,
                        'artist': t_uploader,
                        'query': url,
                        'duration': info.get('duration', 0)
                    }]
                }
    except Exception as e:
        return {'status': 'error', 'message': f'Gagal membaca link YouTube: {str(e)}'}

def main():
    if len(sys.argv) < 2:
        print(json.dumps({'status': 'error', 'message': 'Parameter link URL tidak diberikan.'}, ensure_ascii=False))
        sys.exit(1)
        
    url = sys.argv[1].strip()
    if 'spotify.com' in url.lower():
        res = extract_spotify_metadata(url)
    elif 'youtube.com' in url.lower() or 'youtu.be' in url.lower():
        res = extract_youtube_metadata(url)
    else:
        # Try generic yt-dlp flat extraction
        res = extract_youtube_metadata(url)
        
    print(json.dumps(res, ensure_ascii=False))

if __name__ == '__main__':
    main()
