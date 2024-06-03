<?php

return [
	'ffmpeg.binaries' => env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe'),
	'ffprobe.binaries' => env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe'),
	'timeout' => 3600,
	'threads' => 12,
];
