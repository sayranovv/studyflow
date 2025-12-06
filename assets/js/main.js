class PomodoroTimer {
    constructor(settings) {
        this.settings = settings;
        this.topicId = settings.topicId || null;
        this.isWorkMode = true;
        this.isRunning = false;
        this.isPaused = false;
        this.sessionCount = 0;
        this.intervalId = null;
        this.timeLeft = this.settings.workDuration * 60;

        this.timeEl = document.getElementById('timerDisplay');
        this.statusEl = document.getElementById('phaseLabel');
        this.progressEl = document.getElementById('progressCircle');

        this.updateDisplay();
    }

    start() {
        if (this.isRunning) return;

        this.isRunning = true;
        this.isPaused = false;

        if (!this.intervalId) {
            this.intervalId = setInterval(() => this.tick(), 1000);
        }
    }

    pause() {
        this.isRunning = false;
        this.isPaused = true;

        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    stop() {
        this.isRunning = false;
        this.isPaused = false;

        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }

        this.reset();
    }

    reset() {
        this.isWorkMode = true;
        this.sessionCount = 0;
        this.timeLeft = this.settings.workDuration * 60;
        this.updateStatusText();
        this.updateDisplay();
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
        if (this.isWorkMode && this.topicId) {
            this.sessionCount++;
            this.saveSession();

            const counterEl = document.getElementById('sessionCount');
            if (counterEl) {
                const current = parseInt(counterEl.textContent || '0', 10);
                counterEl.textContent = current + 1;
            }
        }

        const sound = document.getElementById('bellSound');
        if (sound) {
            sound.play().catch(e => console.log('Не удалось воспроизвести звук'));
        }
    }

    switchMode() {
        this.isWorkMode = !this.isWorkMode;
        this.isRunning = false;

        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }

        if (this.isWorkMode) {
            this.timeLeft = this.settings.workDuration * 60;
        } else {
            const isLongBreak = this.sessionCount > 0 &&
                this.sessionCount % this.settings.sessionsBeforeLongBreak === 0;
            this.timeLeft = (isLongBreak ? this.settings.longBreakDuration : this.settings.shortBreakDuration) * 60;
        }

        this.updateStatusText();
        this.updateDisplay();

        if (!this.isWorkMode && this.settings.autoStartBreaks) {
            setTimeout(() => this.start(), 2000);
        }
    }

    updateStatusText() {
        if (this.statusEl) {
            this.statusEl.textContent = this.isWorkMode ? 'Работа' : 'Перерыв';
        }
    }

    updateDisplay() {
        if (this.timeEl) {
            const m = Math.floor(this.timeLeft / 60);
            const s = this.timeLeft % 60;
            this.timeEl.textContent = `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        }

        this.updateStatusText();

        if (this.progressEl) {
            const radius = 45;
            const circumference = 2 * Math.PI * radius;

            const totalTime = (this.isWorkMode ? this.settings.workDuration :
                (this.sessionCount > 0 && this.sessionCount % this.settings.sessionsBeforeLongBreak === 0
                    ? this.settings.longBreakDuration
                    : this.settings.shortBreakDuration)) * 60;

            const progress = 1 - (this.timeLeft / totalTime);
            const offset = circumference * (1 - progress);

            this.progressEl.style.strokeDasharray = `${circumference}`;
            this.progressEl.style.strokeDashoffset = `${offset}`;
        }

        // Обновляем линейный прогресс-бар
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            const totalSessions = parseInt(document.getElementById('sessionCount').nextSibling.textContent.replace('/', '').trim()) || 4;
            const completedSessions = parseInt(document.getElementById('sessionCount').textContent) || 0;
            const progress = (completedSessions / totalSessions) * 100;
            progressFill.style.width = `${progress}%`;
        }
    }

    saveToLocalStorage() {
        const state = {
            topicId: this.topicId,
            isWorkMode: this.isWorkMode,
            timeLeft: this.timeLeft,
            sessionCount: this.sessionCount
        };
        localStorage.setItem('pomodoroState', JSON.stringify(state));
    }

    loadFromLocalStorage() {
        const stored = localStorage.getItem('pomodoroState');
        if (stored) {
            const state = JSON.parse(stored);
            this.topicId = state.topicId;
            this.isWorkMode = state.isWorkMode;
            this.timeLeft = state.timeLeft;
            this.sessionCount = state.sessionCount;
            this.updateDisplay();
        }
    }

    async saveSession() {
        if (!this.topicId) {
            console.warn('No topic selected, session not saved');
            return;
        }

        const sessionData = {
            topic_id: parseInt(this.topicId),
            duration_minutes: this.settings.workDuration,
            session_type: 'work',  // <-- ИСПРАВЛЕНО
            notes: document.getElementById('notes')?.value || ''
        };

        console.log('Saving session:', sessionData);

        try {
            const response = await fetch('/api/sessions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(sessionData)
            });

            const result = await response.json();
            console.log('Save session response:', result);

            if (result.success) {
                console.log('✅ Session saved successfully, ID:', result.session_id);

                const notesEl = document.getElementById('notes');
                if (notesEl) notesEl.value = '';

            } else {
                console.error('❌ Failed to save session:', result.error);
                alert('Не удалось сохранить сессию: ' + result.error);
            }
        } catch (error) {
            console.error('❌ Error saving session:', error);
            alert('Ошибка при сохранении сессии. Проверьте соединение.');
        }
    }

}
