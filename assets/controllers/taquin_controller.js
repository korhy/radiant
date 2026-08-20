import { Controller } from '@hotwired/stimulus';

const SIZE = 4;
const EMPTY_CLASS = 'empty w-16 h-16 bg-tile-empty rounded-lg';

export default class extends Controller {
  static targets = ['grid', 'winOverlay', 'status', 'replay'];

  connect() {
    this.shuffle();
  }

  get gridEl() {
    return this.gridTarget.querySelector('.grid');
  }

  get allTiles() {
    return Array.from(this.gridEl.children);
  }

  /**
   * Takes an element and returns its position in the grid as { row, col }
   * where row and col are 0-indexed.
   * @param {*} el
   * @returns { row: number, col: number }
   */
  positionOf(el) {
    const index = this.allTiles.indexOf(el);
    return { row: Math.floor(index / SIZE), col: index % SIZE };
  }

  /**
   * Checks if a tile can move (i.e. if it's adjacent to the empty space).
   * @param {*} tile
   * @returns {boolean}
   */
  canMove(tile) {
    const empty = this.gridEl.querySelector('.empty');
    const { row: tileRow, col: tileCol } = this.positionOf(tile);
    const { row: emptyRow, col: emptyCol } = this.positionOf(empty);
    // Manhattan distance is calculated as |x1 - x2| + |y1 - y2|.
    // For adjacent tiles, this should equal 1.
    return Math.abs(tileRow - emptyRow) + Math.abs(tileCol - emptyCol) === 1;
  }

  move(event) {
    const tile = event.target.closest('.tile');
    if (!tile || !this.canMove(tile)) return;

    const empty = this.gridEl.querySelector('.empty');
    const label = tile.textContent.trim();

    this.#swap(tile, empty);

    // The tile just moved became the hole, hence disabled: without this handover
    // keyboard focus would fall back to <body>.
    empty.focus();
    this.#announce(`Tuile ${label} déplacée.`);
    this.#checkWin();
  }

  /**
   * Arrows move focus from cell to cell; Enter and Space slide the tile natively,
   * since every cell is a <button>. The hole being disabled, we step over it.
   */
  navigate(event) {
    const deltas = {
      ArrowUp: -SIZE,
      ArrowDown: SIZE,
      ArrowLeft: -1,
      ArrowRight: 1,
    };
    const step = deltas[event.key];
    if (step === undefined) return;

    const tiles = this.allTiles;
    const from = tiles.indexOf(event.target);
    if (from === -1) return;

    event.preventDefault();

    // Two hops at most: the neighbouring cell, then the next if that is the hole.
    for (let hop = 1; hop <= 2; hop++) {
      const to = from + step * hop;
      if (to < 0 || to >= tiles.length) return;
      // Un pas horizontal ne doit pas changer de ligne.
      if (Math.abs(step) === 1 && Math.floor(to / SIZE) !== Math.floor(from / SIZE)) return;

      if (!tiles[to].disabled) {
        tiles[to].focus();
        return;
      }
    }
  }

  replay() {
    this.winOverlayTarget.classList.add('hidden');
    this.shuffle();
  }


  /**
   * Shuffles the tiles by making 200 random valid moves starting from the solved state.
   * This ensures the puzzle is always solvable.
   */
  shuffle() {
    for (let i = 0; i < 200; i++) {
      const empty = this.gridEl.querySelector('.empty');
      const { row, col } = this.positionOf(empty);
      const emptyIndex = this.allTiles.indexOf(empty);

      // Get valid neighbors (tiles that can move into the empty space)
      // Example: if empty is at (1, 1), neighbors are at (0, 1), (2, 1), (1, 0), (1, 2) if they exist
      // We calculate the index of these neighbors in the allTiles array and pick one at random to swap with the empty space.
      const neighbors = [];
      if (row > 0) neighbors.push(emptyIndex - SIZE);
      if (row < SIZE - 1) neighbors.push(emptyIndex + SIZE);
      if (col > 0) neighbors.push(emptyIndex - 1);
      if (col < SIZE - 1) neighbors.push(emptyIndex + 1);

      const randomTile = this.allTiles[neighbors[Math.floor(Math.random() * neighbors.length)]];
      this.#swap(randomTile, empty);
    }

    // Shuffling chains 200 swaps: announce it once, at the end.
    this.#announce('Grille mélangée.');
  }

  #swap(tile, empty) {
    const label = tile.textContent.trim();

    empty.textContent = label;
    empty.className = 'tile';
    empty.disabled = false;
    empty.setAttribute('aria-label', `Tuile ${label}`);

    tile.textContent = '';
    tile.className = EMPTY_CLASS;
    tile.disabled = true;
    tile.setAttribute('aria-label', 'Case vide');
  }

  #announce(message) {
    if (this.hasStatusTarget) this.statusTarget.textContent = message;
  }

  #checkWin() {
    // Check if all tiles are in the correct order and the empty space is last
    const isWin = this.allTiles.every((tile, index) => {
      if (index === SIZE * SIZE - 1) return tile.classList.contains('empty');
      return tile.textContent.trim() === String(index + 1);
    });

    if (isWin) {
      this.winOverlayTarget.classList.remove('hidden');
      this.#announce('Bravo, le puzzle est résolu.');
      // Without this move, focus stays on a cell hidden behind the overlay.
      if (this.hasReplayTarget) this.replayTarget.focus();
    }
  }
}
