import { createFFmpeg, fetchFile } from '@ffmpeg/ffmpeg';

const ffmpeg = await createFFmpeg({ log: true });

async function loadFFmpeg() {
	await ffmpeg.load();
	console.log("FFmpeg loaded successfully!");
}

async function readFile(file) {
	try {
		const response = await fetch(URL.createObjectURL(file));
		const arrayBuffer = await response.arrayBuffer();
		console.log(`File ${file.name} read successfully!`);
		return arrayBuffer;
	} catch (error) {
		throw new Error(`Failed to read file: ${file.name}`);
	}
}

function displaySelectedFiles(files) {
	const container = document.getElementById('selected-audios');
	container.innerHTML = '';
	for (const file of files) {
		const audioElement = document.createElement('audio');
		audioElement.controls = true;
		audioElement.src = URL.createObjectURL(file);
		container.appendChild(audioElement);
	}
}

document.getElementById('audio-files').addEventListener('change', (event) => {
	displaySelectedFiles(event.target.files);
});

// MERGE AUDIO FUNCTIONALITY
document.getElementById('merge-audio').addEventListener('click', async () => {
	const files = document.getElementById('audio-files').files;
	if (files.length < 2) {
		alert('Please select at least two audio files to merge.');
		return;
	}

	try {
		await loadFFmpeg();

		console.log("Merging files:", files);

		for (let i = 0; i < files.length; i++) {
			const fileData = await readFile(files[i]);
			console.log(`Writing file input${i}.mp3`);
			ffmpeg.FS('writeFile', `input${i}.mp3`, new Uint8Array(fileData));
			console.log(`File input${i}.mp3 written successfully!`);
		}

		const inputFiles = files.map((_, i) => `input${i}.mp3`).join('|');
		console.log("Running FFmpeg merge command:", inputFiles);
		await ffmpeg.run('-i', `concat:${inputFiles}`, '-c', 'copy', 'output.mp3');
		console.log("FFmpeg merge command completed!");

		const data = ffmpeg.FS('readFile', 'output.mp3');
		console.log("Output file read:", data);

		const audioURL = URL.createObjectURL(new Blob([data.buffer], { type: 'audio/mp3' }));
		const previewElement = document.getElementById('audio-preview');
		previewElement.src = audioURL;
		previewElement.load();
		previewElement.play();
	} catch (error) {
		console.error('Merge error:', error);
		alert(`An error occurred during merging: ${error.message}`);
	}
});

// ADD BACKGROUND MUSIC FUNCTIONALITY
document.getElementById('add-background-music').addEventListener('click', async () => {
	const files = document.getElementById('audio-files').files;
	if (files.length < 2) {
		alert('Please select an audio file and a background music file.');
		return;
	}

	try {
		await loadFFmpeg();

		const fileData = await readFile(files[0]);
		const backgroundData = await readFile(files[1]);

		ffmpeg.FS('writeFile', 'input.mp3', new Uint8Array(fileData));
		ffmpeg.FS('writeFile', 'background.mp3', new Uint8Array(backgroundData));

		await ffmpeg.run(
			'-i', 'input.mp3',
			'-i', 'background.mp3',
			'-filter_complex',
			'[0:a]volume=1.0[a];[1:a]volume=0.3[b];[a][b]amix=inputs=2:duration=first',
			'output_with_music.mp3'
		);

		const data = ffmpeg.FS('readFile', 'output_with_music.mp3');
		const audioURL = URL.createObjectURL(new Blob([data.buffer], { type: 'audio/mp3' }));
		const previewElement = document.getElementById('audio-preview');
		previewElement.src = audioURL;
		previewElement.load();
		previewElement.play();
	} catch (error) {
		console.error('Add background music error:', error);
		alert(`An error occurred while adding background music: ${error.message}`);
	}
});

// CROP AUDIO FUNCTIONALITY
document.getElementById('crop-audio').addEventListener('click', async () => {
	const files = document.getElementById('audio-files').files;
	if (files.length === 0) {
		alert('Please select an audio file to crop.');
		return;
	}

	const startTime = prompt('Enter the start time for cropping (in seconds):');
	const duration = prompt('Enter the duration for cropping (in seconds):');

	if (!startTime || !duration) {
		alert('Please enter both start time and duration.');
		return;
	}

	try {
		await loadFFmpeg(); // Ensure FFmpeg is loaded before proceeding

		const fileData = await readFile(files[0]);
		ffmpeg.FS('writeFile', 'input.mp3', new Uint8Array(fileData));

		await ffmpeg.run('-i', 'input.mp3', '-ss', startTime, '-t', duration, 'output_cropped.mp3');

		const data = ffmpeg.FS('readFile', 'output_cropped.mp3');
		const audioURL = URL.createObjectURL(new Blob([data.buffer], { type: 'audio/mp3' }));
		const previewElement = document.getElementById('audio-preview');
		previewElement.src = audioURL;
		previewElement.load();
		previewElement.play();
	} catch (error) {
		console.error('Crop error:', error);
		alert(`An error occurred during cropping: ${error.message}`);
	}
});
