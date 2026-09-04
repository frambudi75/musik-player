<?php
/**
 * Generates a clean 16-bit PCM WAV synthesizer ambient track
 */
$songsDir = __DIR__ . '/songs';
if (!is_dir($songsDir)) {
    mkdir($songsDir, 0777, true);
}

$wavPath = $songsDir . '/Midnight_Aura_Ambient.wav';
$lrcPath = $songsDir . '/Midnight_Aura_Ambient.lrc';

if (!file_exists($wavPath)) {
    $sampleRate = 44100;
    $duration = 24; // 24 seconds
    $numSamples = $sampleRate * $duration;
    $numChannels = 2; // Stereo
    $bytesPerSample = 2; // 16-bit
    $byteRate = $sampleRate * $numChannels * $bytesPerSample;
    $blockAlign = $numChannels * $bytesPerSample;
    $dataSize = $numSamples * $blockAlign;
    $fileSize = 36 + $dataSize;

    // WAV Header
    $header = "RIFF" . pack('V', $fileSize) . "WAVE";
    $header .= "fmt " . pack('V', 16) . pack('v', 1) . pack('v', $numChannels) . pack('V', $sampleRate) . pack('V', $byteRate) . pack('v', $blockAlign) . pack('v', 16);
    $header .= "data" . pack('V', $dataSize);

    $fp = fopen($wavPath, 'wb');
    fwrite($fp, $header);

    // Chords frequencies (Am9 - Fmaj7 - C - G)
    $chords = [
        [220.00, 261.63, 329.63, 493.88], // A3, C4, E4, B4
        [174.61, 220.00, 261.63, 329.63], // F3, A3, C4, E4
        [130.81, 196.00, 261.63, 329.63], // C3, G3, C4, E4
        [196.00, 246.94, 293.66, 392.00]  // G3, B3, D4, G4
    ];

    for ($i = 0; $i < $numSamples; $i++) {
        $t = $i / $sampleRate;
        $chordIdx = (int)floor($t / 6) % 4;
        $activeChord = $chords[$chordIdx];

        // Envelope
        $chordTime = fmod($t, 6);
        $env = sin(M_PI * ($chordTime / 6));

        // Synthesize soft sine pad + gentle harmonics + subtle pan
        $sampleL = 0;
        $sampleR = 0;
        foreach ($activeChord as $k => $freq) {
            $osc = sin(2 * M_PI * $freq * $t) * 0.2 + sin(4 * M_PI * $freq * $t) * 0.05;
            $sampleL += $osc * (0.8 - $k * 0.15) * $env;
            $sampleR += $osc * (0.3 + $k * 0.15) * $env;
        }

        // Sub bass kick pulse every 2 seconds
        $beatTime = fmod($t, 2);
        if ($beatTime < 0.4) {
            $kick = sin(2 * M_PI * 55 * $beatTime) * exp(-$beatTime * 8) * 0.35;
            $sampleL += $kick;
            $sampleR += $kick;
        }

        // 16-bit clamp
        $valL = (int)max(-32767, min(32767, $sampleL * 22000));
        $valR = (int)max(-32767, min(32767, $sampleR * 22000));

        fwrite($fp, pack('v', $valL < 0 ? $valL + 65536 : $valL));
        fwrite($fp, pack('v', $valR < 0 ? $valR + 65536 : $valR));
    }
    fclose($fp);
}

// Write synchronized LRC lyrics demo
if (!file_exists($lrcPath)) {
    $lrcContent = "[00:00.00]♪ Aura Music - Sound System Initialized ♪\n" .
                  "[00:02.50]Selamat datang di pemutar musik pribadi Anda\n" .
                  "[00:06.00]Tampilan elegan dengan palet editorial gelap matte\n" .
                  "[00:10.00]Visualizer frekuensi audio real-time bertenaga Web Audio API\n" .
                  "[00:14.50]Sinkronisasi lirik kata per kata otomatis\n" .
                  "[00:18.00]Equalizer 10-Band dan Bass Boost aktif\n" .
                  "[00:21.00]♪ Rasakan kualitas audio murni tanpa kompromi ♪\n";
    file_put_contents($lrcPath, $lrcContent);
}

echo "Demo sound & synced lyrics created successfully!";
