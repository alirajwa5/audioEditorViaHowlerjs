<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AudioController;
use FFMpeg\FFMpeg;
use Illuminate\Http\Request;
use App\Http\Controllers\AuphonicController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('audio-editor');
});
Route::post('/upload', [AudioController::class, 'upload']);


Route::post('/merge-audio', function (Request $request) {
	// Get the audio files from the request
	$files = $request->file('audio_files');

	// Check if there are at least two files
	if (count($files) < 2) {
		return response()->json(['error' => 'Please select at least two audio files to merge.'], 400);
	}

	// Create a new FFMpeg instance
	$ffmpeg = FFMpeg::create();

	// Create a new FFMpeg audio instance
	$audio = $ffmpeg->open($files[0]->getPathname());

	// Merge the audio files
	foreach (array_slice($files, 1) as $file) {
		$audio->add($ffmpeg->open($file->getPathname()));
	}

	// Save the merged audio file
	$audio->export()
	      ->toFormat('mp3')
	      ->save('merged.mp3');

	// Return the merged audio file as a response
	return response()->download('merged.mp3');
})->name('merge-audio');

Route::get('/process-audio', [AuphonicController::class, 'processAudio']);


Route::post('/merge-audio', [AudioController::class, 'mergeAudio']);
Route::post('/crop-audio', [AudioController::class, 'cropAudio']);
Route::post('/add-background-music', [AudioController::class, 'addBackgroundMusic']);
Route::get('/test-ffmpeg', [App\Http\Controllers\AudioController::class, 'testFFmpeg']);


Route::get('/env-test', function () {
	return [
		'FFMPEG_BIN_PATH' => env('FFMPEG_BIN_PATH'),
		'FFPROBE_BIN_PATH' => env('FFPROBE_BIN_PATH'),
	];
});
