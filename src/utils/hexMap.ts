export interface HexCell {
  id: string;
  row: number;
  col: number;
  label: string;
  points: string;
}

export const HEX_MAP_WIDTH = 1668;
export const HEX_MAP_HEIGHT = 1000;
export const HEX_GRID_ROWS = 12;
export const HEX_GRID_COLS = 20;
export const HEX_GRID_PADDING = 38;
export const HEX_MAP_STORAGE_KEY = 'ow-admin-hex-map-v1';

export function buildHexGrid(): HexCell[] {
  const widthLimitedSize = (HEX_MAP_WIDTH - HEX_GRID_PADDING * 2) / (Math.sqrt(3) * (HEX_GRID_COLS + 0.5));
  const heightLimitedSize = (HEX_MAP_HEIGHT - HEX_GRID_PADDING * 2) / (2 + (HEX_GRID_ROWS - 1) * 1.5);
  const size = Math.min(widthLimitedSize, heightLimitedSize);
  const hexWidth = Math.sqrt(3) * size;
  const startX = (HEX_MAP_WIDTH - (HEX_GRID_COLS * hexWidth + hexWidth / 2)) / 2 + hexWidth / 2;
  const startY = (HEX_MAP_HEIGHT - (2 * size + (HEX_GRID_ROWS - 1) * 1.5 * size)) / 2 + size;
  const cells: HexCell[] = [];

  for (let row = 0; row < HEX_GRID_ROWS; row += 1) {
    for (let col = 0; col < HEX_GRID_COLS; col += 1) {
      const centerX = startX + col * hexWidth + (row % 2 === 1 ? hexWidth / 2 : 0);
      const centerY = startY + row * size * 1.5;
      cells.push({
        id: `${row}-${col}`,
        row,
        col,
        label: `R${row + 1} C${col + 1}`,
        points: buildHexPoints(centerX, centerY, size),
      });
    }
  }

  return cells;
}

export function loadStoredHexAssignments(): Record<string, string> {
  if (typeof window === 'undefined') {
    return {};
  }

  const stored = window.localStorage.getItem(HEX_MAP_STORAGE_KEY);
  if (!stored) {
    return {};
  }

  try {
    const parsed = JSON.parse(stored) as { assignments?: Record<string, string> };
    return parsed.assignments ?? {};
  } catch {
    return {};
  }
}

function buildHexPoints(centerX: number, centerY: number, size: number) {
  const points = Array.from({ length: 6 }, (_, index) => {
    const angle = ((60 * index - 30) * Math.PI) / 180;
    const x = centerX + size * Math.cos(angle);
    const y = centerY + size * Math.sin(angle);
    return `${x.toFixed(2)},${y.toFixed(2)}`;
  });

  return points.join(' ');
}
