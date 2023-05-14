@extends('music::layouts.master')

@section('content')
@livewireStyles
    <h1>Hello World</h1>
    <p>
        <livewire:player>   
        <div id="waveform"></div>
        
        This view is loaded from module: {!! config('music.name') !!}
    </p>
    <style>
      canvas {
        border: 1px solid #000;
      }
      #frequencyTimeline {
        width: 600px;
        height: 100px;
        border: 1px solid #000;
      }

      #progressBar {
      width: 600px;
      height: 10px;
      background-color: #ccc;
    }
    #progress {
      width: 0;
      height: 10px;
      background-color: #ff0000;
    }

    #dragBall {
      width: 10px;
      height: 10px;
      background-color: #0000ff;
      position: absolute;
      top: -5px;
      left: 0;
      cursor: grab;
    }
    </style>
  
    <canvas id="visualizer" width="600" height="200"></canvas>
    <div id="frequencyTimeline"></div>
    <div id="progressBar">
      <div id="progress"></div>
      <div id="dragBall"></div>
    </div>
  <button id="playButton">Play</button>

  <script>

  // Inicializa o contexto de áudio
  const audioContext = new (window.AudioContext || window.webkitAudioContext)();

  // Carrega o arquivo de áudio
  const audioElement = new Audio('{{url("audio.wav")}}');

  // Conecta o áudio ao contexto
  const audioSource = audioContext.createMediaElementSource(audioElement);

  // Cria um analisador de áudio para as ondas
  const waveAnalyser = audioContext.createAnalyser();
  waveAnalyser.fftSize = 2048; // Tamanho da transformada de Fourier para as ondas

  // Cria um analisador de áudio para as frequências
  const frequencyAnalyser = audioContext.createAnalyser();
  frequencyAnalyser.fftSize = 2048; // Tamanho da transformada de Fourier para as frequências

  // Conecta o áudio aos analisadores
  audioSource.connect(waveAnalyser);
  audioSource.connect(frequencyAnalyser);
  waveAnalyser.connect(audioContext.destination);

  // Configura o canvas para as ondas
  const canvas = document.getElementById('visualizer');
  const canvasContext = canvas.getContext('2d');

  // Array para armazenar os dados das ondas
  const bufferLength = waveAnalyser.frequencyBinCount;
  const dataArray = new Uint8Array(bufferLength);

  // Configura o elemento para o timeline das frequências
  const frequencyTimeline = document.getElementById('frequencyTimeline');
  frequencyTimeline.style.position = 'relative';

  const frequencyCanvas = document.createElement('canvas');
  frequencyCanvas.width = frequencyTimeline.clientWidth;
  frequencyCanvas.height = frequencyTimeline.clientHeight;
  frequencyTimeline.appendChild(frequencyCanvas);

  const frequencyCanvasContext = frequencyCanvas.getContext('2d');

  // Elemento de progresso
  const progressBar = document.getElementById('progress');
  const progressBarWidth = document.getElementById('progressBar').offsetWidth;
  const dragBall = document.getElementById('dragBall');

  // Função para desenhar as ondas
  function drawWaves() {
    // Limpa o canvas das ondas
    canvasContext.clearRect(0, 0, canvas.width, canvas.height);

    // Obtém os dados das ondas
    waveAnalyser.getByteTimeDomainData(dataArray);

    // Configura as propriedades de desenho
    canvasContext.lineWidth = 2;
    canvasContext.strokeStyle = 'rgb(0, 0, 0)';
    canvasContext.beginPath();

    const sliceWidth = canvas.width * 1.0 / bufferLength;
    let x = 0;

    // Desenha as ondas
    for (let i = 0; i < bufferLength; i++) {
      const v = dataArray[i] / 128.0;
      const y = v * canvas.height / 2;

      if (i === 0) {
        canvasContext.moveTo(x, y);
      } else {
        canvasContext.lineTo(x, y);
      }

      x += sliceWidth;
    }

    // Finaliza o desenho
    canvasContext.lineTo(canvas.width, canvas.height / 2);
    canvasContext.stroke();

    // Atualiza a função de desenho
    requestAnimationFrame(drawWaves);
  }

  // Função para atualizar o progresso da música
  function updateProgress() {
    const currentTime = audioElement.currentTime;
    const duration = audioElement.duration;
    const progressWidth = (currentTime / duration) * progressBarWidth;

    progressBar.style.width = `${progressWidth}px`;
    dragBall.style.left = `${progressWidth}px`;
    requestAnimationFrame(updateProgress);
  }

// Evento de clique no botão de play
const playButton = document.getElementById('playButton');
playButton.addEventListener('click', function () {
if (audioContext.state === 'suspended') {
audioContext.resume();
}
if (audioElement.paused) {
  audioElement.play();
  drawWaves();
  updateProgress();
  playButton.innerText = 'Pause';
} else {
  audioElement.pause();
  playButton.innerText = 'Play';
}
});

// Variáveis para controle do arraste
let isDragging = false;
let dragStartX = 0;
let progressAtDragStart = 0;

// Eventos de arraste da bolinha
dragBall.addEventListener('mousedown', function (event) {
isDragging = true;
dragStartX = event.clientX;
progressAtDragStart = (audioElement.currentTime / audioElement.duration) * progressBarWidth;
dragBall.style.cursor = 'grabbing';
});

dragBall.addEventListener('mousemove', function (event) {
if (isDragging) {
const dragOffsetX = event.clientX - dragStartX;
let newProgress = progressAtDragStart + dragOffsetX;
if (newProgress < 0) {
    newProgress = 0;
  } else if (newProgress > progressBarWidth) {
    newProgress = progressBarWidth;
  }

  const newTime = (newProgress / progressBarWidth) * audioElement.duration;
  audioElement.currentTime = newTime;

  progressBar.style.width = `${newProgress}px`;
  dragBall.style.left = `${newProgress}px`;
}
});

dragBall.addEventListener('mouseup', function () {
if (isDragging) {
isDragging = false;
dragBall.style.cursor = 'grab';
  // Atualiza o tempo da música após o arraste
  const newTime = (parseFloat(progressBar.style.width) / progressBarWidth) * audioElement.duration;
  audioElement.currentTime = newTime;
}
});

// Evento de atualização do tamanho da janela
window.addEventListener('resize', function () {
// Redimensiona o canvas das frequências
frequencyCanvas.width = frequencyTimeline.clientWidth;
frequencyCanvas.height = frequencyTimeline.clientHeight;
});

// Função para desenhar as frequências
function drawFrequencies() {
// Limpa o canvas das frequências
frequencyCanvasContext.clearRect(0, 0, frequencyCanvas.width, frequencyCanvas.height);
// Obtém os dados das frequências
const bufferLength = frequencyAnalyser.frequencyBinCount;
const dataArray = new Uint8Array(bufferLength);
frequencyAnalyser.getByteFrequencyData(dataArray);

// Configura as propriedades de desenho
frequencyCanvasContext.fillStyle = 'rgb(0, 0, 0)';
const barWidth = (frequencyCanvas.width / bufferLength) * 2;
let x = 0;

// Desenha as barras de frequência
for (let i = 0; i < bufferLength; i++) {
  const barHeight = (dataArray[i] / 255) * frequencyCanvas.height;

  frequencyCanvasContext.fillRect(x, frequencyCanvas.height - barHeight, barWidth, barHeight);

  x += barWidth + 1;
}

// Atualiza a função de desenho
requestAnimationFrame(drawFrequencies);
}

// Inicia o visualizador de frequências
drawFrequencies();
</script>


@livewireScripts
@endsection