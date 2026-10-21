// Mirrors App\Services\HPBarService - keep the segment / damage maths in sync.

export const PHASE_SLOTS = 10;

/**
 * Split max HP into per-bar ranges. Bar 1 holds the top slice and drains first.
 */
export function segments(max, splits) {
  const result = [];
  let to = max;
  let remainingPct = 100;

  splits.forEach((split, i) => {
    remainingPct -= parseInt(split);
    const from = i === splits.length - 1
      ? 0
      : Math.round(max * Math.max(remainingPct, 0) / 100);
    result.push({ from, to });
    to = from;
  });

  return result;
}

/**
 * The bars to render: a single full bar, or one entry per split.
 */
export function activeSegments(options, max) {
  if (!options?.multi?.enabled) {
    return [{ from: 0, to: max }];
  }
  return segments(max, options.multi.splits);
}

/**
 * How full a segment is, 0-100.
 */
export function segmentFill(segment, current) {
  const size = segment.to - segment.from;
  if (size <= 0) {
    return 0;
  }
  const filled = Math.min(Math.max(current - segment.from, 0), size);
  return (filled / size) * 100;
}

/**
 * Lowest HP value the current bar can be damaged down to.
 */
export function segmentFloor(options, max, current) {
  if (!options?.multi?.enabled || !options.multi.capped) {
    return 0;
  }
  const segment = segments(max, options.multi.splits).find((s) => current > s.from);
  return segment ? segment.from : 0;
}

/**
 * "At or below X%, the bar turns this colour" - pick the enabled phase with
 * the lowest threshold that is still >= the fill percentage.
 */
export function phaseColor(options, percent) {
  const colors = options?.colors;
  if (!colors) {
    return '#22c55e';
  }
  if (colors.mode === 'solid') {
    return colors.solid;
  }

  const match = colors.phases
    .filter((phase) => phase.enabled && percent <= phase.threshold)
    .sort((a, b) => a.threshold - b.threshold)[0];

  return match ? match.color : colors.solid;
}

/**
 * Shield absorbs damage first, then HP down to the segment floor.
 */
export function applyDamage({ current, max, shield }, amount, options) {
  amount = parseInt(amount);
  if (!(amount > 0)) {
    return { current, shield };
  }

  if (shield > 0) {
    const absorbed = Math.min(shield, amount);
    shield -= absorbed;
    amount -= absorbed;
  }

  if (amount > 0) {
    current = Math.max(current - amount, segmentFloor(options, max, current));
  }

  return { current, shield };
}
