import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['grid', 'winOverlay'];

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
    return { row: Math.floor(index / 4), col: index % 4 };
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
    this.#swap(tile, empty);
    this.#checkWin();
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
      if (row > 0) neighbors.push(emptyIndex - 4);
      if (row < 3) neighbors.push(emptyIndex + 4);
      if (col > 0) neighbors.push(emptyIndex - 1);
      if (col < 3) neighbors.push(emptyIndex + 1);

      const randomTile = this.allTiles[neighbors[Math.floor(Math.random() * neighbors.length)]];
      this.#swap(randomTile, empty);
    }
  }

  #swap(tile, empty) {
    empty.textContent = tile.textContent.trim();
    empty.className = 'tile';
    tile.textContent = '';
    tile.className = 'empty w-16 h-16 bg-slate-700 rounded-lg';
  }

  #checkWin() {
    // Check if all tiles are in the correct order and the empty space is last
    const isWin = this.allTiles.every((tile, index) => {
      if (index === 15) return tile.classList.contains('empty');
      return tile.textContent.trim() === String(index + 1);
    });

    if (isWin) {
      this.winOverlayTarget.classList.remove('hidden');
    }
  }
}
