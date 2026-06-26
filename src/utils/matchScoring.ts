export function calculateMatchPoints(playerAVictoryPoints: number, playerBVictoryPoints: number) {
  const difference = Math.abs(playerAVictoryPoints - playerBVictoryPoints);

  if (difference <= 450) {
    return { playerA: 3, playerB: 3 };
  }

  if (difference <= 950) {
    return playerAVictoryPoints > playerBVictoryPoints
      ? { playerA: 4, playerB: 2 }
      : { playerA: 2, playerB: 4 };
  }

  if (difference <= 1400) {
    return playerAVictoryPoints > playerBVictoryPoints
      ? { playerA: 5, playerB: 1 }
      : { playerA: 1, playerB: 5 };
  }

  return playerAVictoryPoints > playerBVictoryPoints
    ? { playerA: 6, playerB: 0 }
    : { playerA: 0, playerB: 6 };
}

export function battleOutcomeLabel(leftScore: number, rightScore: number) {
  if (leftScore === rightScore) {
    return 'Pareggio';
  }

  const isWin = leftScore > rightScore;
  const winningScore = Math.max(leftScore, rightScore);

  if (winningScore === 4) {
    return isWin ? 'Vittoria Minore' : 'Sconfitta Minore';
  }

  if (winningScore === 5) {
    return isWin ? 'Vittoria Maggiore' : 'Sconfitta Maggiore';
  }

  if (winningScore === 6) {
    return isWin ? 'Massacro' : 'Sconfitta Totale';
  }

  return isWin ? 'Vittoria' : 'Sconfitta';
}
