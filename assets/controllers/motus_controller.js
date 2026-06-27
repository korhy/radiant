import { Controller } from '@hotwired/stimulus';

const MAX_ATTEMPTS = 6;
const STORAGE_KEY = 'motus_session';

export default class extends Controller {
    static targets = ['grid', 'keyboard', 'message'];
    static values = {
        wordLength: Number,
        firstLetter: String,
        guessUrl: String,
    };

    // Each entry: { guess: string, result: [{letter, state}] }
    #attempts = [];
    #currentGuess = [];
    #gameOver = false;

    connect() {
        this.#buildGrid();
        const restored = this.#loadSession();
        if (restored) {
            this.#restoreSession(restored);
        } else {
            this.#prefillFirstLetter();
        }
        document.addEventListener('keydown', this.#onKeyDown);
    }

    disconnect() {
        document.removeEventListener('keydown', this.#onKeyDown);
    }

    keyPress(e) {
        this.#addLetter(e.currentTarget.dataset.key);
    }

    backspace() {
        this.#removeLetter();
    }

    async submit() {
        await this.#submitGuess();
    }

    // --- Session persistence ---

    #todayKey() {
        return new Date().toISOString().slice(0, 10); // "YYYY-MM-DD"
    }

    #saveSession() {
        const session = {
            date: this.#todayKey(),
            wordLength: this.wordLengthValue,
            firstLetter: this.firstLetterValue,
            attempts: this.#attempts,
            gameOver: this.#gameOver,
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
    }

    #loadSession() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            const session = JSON.parse(raw);
            // Stale if different day or different word config
            if (
                session.date !== this.#todayKey() ||
                session.wordLength !== this.wordLengthValue ||
                session.firstLetter !== this.firstLetterValue
            ) {
                localStorage.removeItem(STORAGE_KEY);
                return null;
            }
            return session;
        } catch {
            return null;
        }
    }

    #restoreSession(session) {
        this.#attempts = session.attempts;
        this.#gameOver = session.gameOver;

        // Replay all past attempts visually
        this.#attempts.forEach(({ result }, row) => {
            this.#applyResult(row, result);
            this.#updateKeyboard(result);
        });

        if (this.#gameOver) {
            const won = this.#attempts.at(-1)?.result.every(r => r.state === 'correct');
            this.#showMessage(
                won
                    ? '🎉 Bravo ! Vous avez trouvé le mot !'
                    : '😔 Perdu ! Revenez demain pour un nouveau mot.'
            );
            return;
        }

        this.#prefillFirstLetter();
    }

    // --- Private ---

    #onKeyDown = (e) => {
        if (this.#gameOver) return;
        if (e.key === 'Enter') { this.#submitGuess(); return; }
        if (e.key === 'Backspace') { this.#removeLetter(); return; }
        const letter = e.key.toUpperCase();
        if (/^[A-Z]$/.test(letter)) this.#addLetter(letter);
    };

    #buildGrid() {
        this.gridTarget.innerHTML = '';
        for (let row = 0; row < MAX_ATTEMPTS; row++) {
            const rowEl = document.createElement('div');
            rowEl.classList.add('flex', 'gap-1.5');
            rowEl.dataset.row = row;
            for (let col = 0; col < this.wordLengthValue; col++) {
                const cell = document.createElement('div');
                cell.classList.add(
                    'w-10', 'h-10', 'md:w-12', 'md:h-12',
                    'flex', 'items-center', 'justify-center',
                    'rounded', 'text-white', 'font-bold', 'text-lg',
                    'border-2', 'border-slate-600', 'bg-slate-700',
                    'transition-colors', 'duration-300',
                    'uppercase'
                );
                cell.dataset.row = row;
                cell.dataset.col = col;
                rowEl.appendChild(cell);
            }
            this.gridTarget.appendChild(rowEl);
        }
    }

    #prefillFirstLetter() {
        this.#currentGuess = [this.firstLetterValue];
        this.#renderCurrentRow();
    }

    #addLetter(letter) {
        if (this.#gameOver) return;
        if (this.#currentGuess.length >= this.wordLengthValue) return;
        this.#currentGuess.push(letter);
        this.#renderCurrentRow();
    }

    #removeLetter() {
        if (this.#gameOver) return;
        if (this.#currentGuess.length <= 1) return; // keep first letter
        this.#currentGuess.pop();
        this.#renderCurrentRow();
    }

    #renderCurrentRow() {
        const row = this.#attempts.length;
        for (let col = 0; col < this.wordLengthValue; col++) {
            const cell = this.#getCell(row, col);
            cell.textContent = this.#currentGuess[col] ?? '';
            if (col === 0) {
                cell.classList.add('bg-red-500', 'border-red-500');
            }
        }
    }

    async #submitGuess() {
        if (this.#gameOver) return;
        if (this.#currentGuess.length !== this.wordLengthValue) {
            this.#showMessage(`Mot de ${this.wordLengthValue} lettres requis`);
            return;
        }

        const guess = this.#currentGuess.join('');
        let data;
        try {
            const res = await fetch(this.guessUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ guess }),
            });
            data = await res.json();
        } catch {
            this.#showMessage('Erreur réseau');
            return;
        }

        const row = this.#attempts.length;
        this.#attempts.push({ guess, result: data.result });
        this.#applyResult(row, data.result);
        this.#updateKeyboard(data.result);

        if (data.won) {
            this.#gameOver = true;
            this.#saveSession();
            this.#showMessage('🎉 Bravo ! Vous avez trouvé le mot !');
            return;
        }

        if (this.#attempts.length >= MAX_ATTEMPTS) {
            this.#gameOver = true;
            this.#saveSession();
            this.#showMessage('😔 Perdu ! Revenez demain pour un nouveau mot.');
            return;
        }

        this.#saveSession();
        this.#currentGuess = [this.firstLetterValue];
        this.#renderCurrentRow();
        this.#showMessage('');
    }

    #applyResult(row, result) {
        result.forEach(({ letter, state }, col) => {
            const cell = this.#getCell(row, col);
            cell.textContent = letter;
            cell.classList.remove('bg-slate-700', 'border-slate-600');
            if (state === 'correct') {
                cell.classList.add('bg-red-500', 'border-red-500');
            } else if (state === 'present') {
                cell.classList.add('bg-yellow-400', 'border-yellow-400', 'text-slate-900');
            } else {
                cell.classList.add('bg-slate-600', 'border-slate-600');
            }
        });
    }

    #updateKeyboard(result) {
        const priority = { correct: 3, present: 2, absent: 1 };
        result.forEach(({ letter, state }) => {
            const btn = this.keyboardTarget.querySelector(`[data-key="${letter}"]`);
            if (!btn) return;
            const current = btn.dataset.state ?? 'none';
            if ((priority[state] ?? 0) > (priority[current] ?? 0)) {
                btn.dataset.state = state;
                btn.classList.remove('bg-slate-600', 'bg-red-500', 'bg-yellow-400', 'bg-slate-500');
                if (state === 'correct') btn.classList.add('bg-red-500');
                else if (state === 'present') btn.classList.add('bg-yellow-400', 'text-slate-900');
                else btn.classList.add('bg-slate-500', 'opacity-50');
            }
        });
    }

    #getCell(row, col) {
        return this.gridTarget.querySelector(`[data-row="${row}"][data-col="${col}"]`);
    }

    #showMessage(text) {
        this.messageTarget.textContent = text;
    }
}
