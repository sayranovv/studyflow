class PomodoroTimer {
    constructor(settings) {
        this.settings = settings;
        this.isWorkMode = true;
        this.isRunning = false;
        this.sessionCount = 0;
        this.intervalId = null;
        this.timeLeft = this.settings.workDuration * 60;

        this.timeEl = document.getElementById('timerTime');
        this.statusEl = document.getElementById('timerStatus');
        this.progressEl = document.getElementById('timerProgress');

        this.updateDisplay();
        this.updateButtonsVisibility();
    }

    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        this.updateButtonsVisibility();

        if (!this.intervalId) {
            this.intervalId = setInterval(() => this.tick(), 1000);
        }
    }

    pause() {
        this.isRunning = false;
        this.updateButtonsVisibility();
    }

    reset() {
        this.isRunning = false;
        clearInterval(this.intervalId);
        this.intervalId = null;
        this.isWorkMode = true;
        this.sessionCount = 0;
        this.timeLeft = this.settings.workDuration * 60;
        this.updateStatusText();
        this.updateDisplay();
        this.updateButtonsVisibility();
    }

    tick() {
        if (!this.isRunning) return;

        this.timeLeft--;
        this.updateDisplay();

        if (this.timeLeft <= 0) {
            this.completeSession();
            this.switchMode();
        }
    }

    completeSession() {
        if (this.isWorkMode) {
            this.sessionCount++;

            if (typeof CURRENT_TOPIC_ID !== 'undefined' && CURRENT_TOPIC_ID) {
                this.saveSession();
            }

            const counterEl = document.getElementById('sessionCount');
            if (counterEl) {
                const current = parseInt(counterEl.textContent || '0', 10);
                counterEl.textContent = current + 1;
            }
        }

        if (this.settings.soundEnabled) {
            const sound = document.getElementById('bellSound');
            if (sound) sound.play();
        }
    }

    switchMode() {
        this.isWorkMode = !this.isWorkMode;
        this.isRunning = false;
        clearInterval(this.intervalId);
        this.intervalId = null;

        if (this.isWorkMode) {
            this.timeLeft = this.settings.workDuration * 60;
        } else {
            const isLongBreak = this.sessionCount > 0 && this.sessionCount % this.settings.longBreakInterval === 0;
            this.timeLeft = (isLongBreak ? this.settings.longBreak : this.settings.shortBreak) * 60;
        }

        this.updateStatusText();
        this.updateDisplay();
        this.updateButtonsVisibility();

        if (!this.isWorkMode && this.settings.autoStartBreaks) {
            setTimeout(() => this.start(), 1000);
        }
    }

    updateStatusText() {
        if (!this.statusEl) return;
        this.statusEl.textContent = this.isWorkMode ? 'Фокус' : 'Перерыв';
    }

    updateDisplay() {
        if (this.timeEl) {
            const m = Math.floor(this.timeLeft / 60);
            const s = this.timeLeft % 60;
            this.timeEl.textContent = `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        }

        if (this.progressEl) {
            const radius = 140;
            const circumference = 2 * Math.PI * radius;
            const totalTime = (this.isWorkMode ? this.settings.workDuration :
                (this.sessionCount > 0 && this.sessionCount % this.settings.longBreakInterval === 0
                    ? this.settings.longBreak
                    : this.settings.shortBreak)) * 60;
            const progress = 1 - (this.timeLeft / totalTime);
            this.progressEl.style.strokeDasharray = `${circumference}`;
            this.progressEl.style.strokeDashoffset = `${circumference * (1 - progress)}`;
        }
    }

    updateButtonsVisibility() {
        const startBtn = document.getElementById('startBtn');
        const pauseBtn = document.getElementById('pauseBtn');
        if (!startBtn || !pauseBtn) return;

        if (this.isRunning) {
            startBtn.style.display = 'none';
            pauseBtn.style.display = 'inline-flex';
        } else {
            startBtn.style.display = 'inline-flex';
            pauseBtn.style.display = 'none';
        }
    }

    async saveSession() {
        try {
            const response = await fetch('/api/sessions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    topic_id: CURRENT_TOPIC_ID,
                    duration_minutes: this.settings.workDuration,
                    session_type: 'new_material'
                })
            });

            const result = await response.json();
            if (!result.success) {
                console.error('Failed to save session:', result.error);
            }
        } catch (error) {
            console.error('Error saving session:', error);
        }
    }
}

let timer;
if (typeof TIMER_SETTINGS !== 'undefined') {
    timer = new PomodoroTimer(TIMER_SETTINGS);
}
