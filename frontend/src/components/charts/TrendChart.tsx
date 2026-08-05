"use client";

import {
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";

const COLORS: Record<string, string> = {
  positive: "#10b981",
  negative: "#ef4444",
  neutral: "#9ca3af",
};

export default function TrendChart({
  labels,
  series,
}: {
  labels: string[];
  series: Array<{ name: string; data: number[] }>;
}) {
  const data = labels.map((label, index) => {
    const row: Record<string, number | string> = { label };
    for (const s of series) row[s.name] = s.data[index] ?? 0;
    return row;
  });

  return (
    <ResponsiveContainer width="100%" height={280}>
      <LineChart data={data} margin={{ top: 8, right: 16, bottom: 0, left: -16 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
        <XAxis dataKey="label" tick={{ fontSize: 12 }} />
        <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
        <Tooltip />
        <Legend />
        {series.map((s) => (
          <Line
            key={s.name}
            type="monotone"
            dataKey={s.name}
            stroke={COLORS[s.name] ?? "#6366f1"}
            strokeWidth={2}
            dot={{ r: 3 }}
          />
        ))}
      </LineChart>
    </ResponsiveContainer>
  );
}
