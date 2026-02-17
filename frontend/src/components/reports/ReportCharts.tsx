"use client";

import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  Legend,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
  CartesianGrid,
} from "recharts";
import ChartCard from "./ChartCard";
import { formatNumber } from "@/lib/formatters";
import { useCurrency } from "@/hooks/useCurrency";
import type { ReportDashboardData } from "@/types/reports";

const PRIMARY = "#3B82F6";
const GREEN = "#10B981";
const AMBER = "#F59E0B";
const GRAY = "#9CA3AF";

const STATUS_COLORS: Record<string, string> = {
  draft: GRAY,
  calculated: GREEN,
  finalized: PRIMARY,
};

interface Props {
  data: ReportDashboardData;
}

function EmptyState({ message }: { message: string }) {
  return (
    <div className="h-full flex items-center justify-center text-sm text-gray-400">
      {message}
    </div>
  );
}

/** Compact number formatter for axis ticks */
function compactNumber(v: number): string {
  if (v >= 1000000) return `${(v / 1000000).toFixed(1)}M`;
  if (v >= 1000) return `${(v / 1000).toFixed(0)}K`;
  return String(v);
}

export default function ReportCharts({ data }: Props) {
  const { symbol, rate } = useCurrency();
  // Weight distribution donut data
  const weightData = [
    {
      name: "Steel",
      value: data.weight_distribution.steel_weight_kg,
      color: PRIMARY,
    },
    {
      name: "Panels",
      value: data.weight_distribution.panels_weight_kg,
      color: GREEN,
    },
  ].filter((d) => d.value > 0);

  // Status donut data
  const statusData = data.status_breakdown
    .map((s) => ({
      name: s.status.charAt(0).toUpperCase() + s.status.slice(1),
      value: s.count,
      color: STATUS_COLORS[s.status] || GRAY,
    }))
    .filter((d) => d.value > 0);

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      {/* 1. Monthly Estimation Trends */}
      <ChartCard title="Monthly Estimation Trends" span={2}>
        {data.monthly_trends.length === 0 ? (
          <EmptyState message="No trend data available" />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={data.monthly_trends}
              margin={{ top: 5, right: 20, bottom: 5, left: 0 }}
            >
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis
                dataKey="label"
                tick={{ fontSize: 11 }}
                tickLine={false}
              />
              <YAxis
                yAxisId="left"
                tick={{ fontSize: 11 }}
                tickLine={false}
                axisLine={false}
              />
              <YAxis
                yAxisId="right"
                orientation="right"
                tick={{ fontSize: 11 }}
                tickLine={false}
                axisLine={false}
                tickFormatter={compactNumber}
              />
              <Tooltip
                formatter={(value, name) => [
                  name === `Revenue (${symbol})`
                    ? `${formatNumber(Number(value) * rate, 0)} ${symbol}`
                    : formatNumber(Number(value), 0),
                  name,
                ]}
              />
              <Legend />
              <Bar
                yAxisId="left"
                dataKey="count"
                name="Estimations"
                fill={PRIMARY}
                radius={[4, 4, 0, 0]}
              />
              <Bar
                yAxisId="right"
                dataKey="revenue"
                name={`Revenue (${symbol})`}
                fill={GREEN}
                radius={[4, 4, 0, 0]}
                opacity={0.7}
              />
            </BarChart>
          </ResponsiveContainer>
        )}
      </ChartCard>

      {/* 2. Top Customers by Revenue */}
      <ChartCard title="Top Customers by Revenue">
        {data.customer_revenue.length === 0 ? (
          <EmptyState message="No customer data available" />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={data.customer_revenue}
              layout="vertical"
              margin={{ top: 5, right: 20, bottom: 5, left: 10 }}
            >
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis
                type="number"
                tick={{ fontSize: 10 }}
                tickLine={false}
                tickFormatter={compactNumber}
              />
              <YAxis
                type="category"
                dataKey="customer_name"
                tick={{ fontSize: 10 }}
                tickLine={false}
                width={100}
              />
              <Tooltip
                formatter={(value) => [
                  `${formatNumber(Number(value) * rate, 0)} ${symbol}`,
                  "Revenue",
                ]}
              />
              <Bar
                dataKey="total_price_aed"
                fill={PRIMARY}
                radius={[0, 4, 4, 0]}
              />
            </BarChart>
          </ResponsiveContainer>
        )}
      </ChartCard>

      {/* 3. Weight Distribution (Donut) */}
      <ChartCard title="Weight Distribution">
        {weightData.length === 0 ? (
          <EmptyState message="No weight data available" />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie
                data={weightData}
                cx="50%"
                cy="50%"
                innerRadius={60}
                outerRadius={90}
                paddingAngle={3}
                dataKey="value"
                label={({ name, percent }) =>
                  `${name} ${((percent ?? 0) * 100).toFixed(1)}%`
                }
                labelLine={false}
              >
                {weightData.map((entry, index) => (
                  <Cell key={`wt-${index}`} fill={entry.color} />
                ))}
              </Pie>
              <Tooltip
                formatter={(value) => [
                  `${formatNumber(Number(value), 0)} kg`,
                  "Weight",
                ]}
              />
              <Legend />
            </PieChart>
          </ResponsiveContainer>
        )}
      </ChartCard>

      {/* 4. Price per MT Trend */}
      <ChartCard title="Price per MT Trend">
        {data.price_per_mt_trend.length === 0 ? (
          <EmptyState message="No pricing trend data available" />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <LineChart
              data={data.price_per_mt_trend}
              margin={{ top: 5, right: 20, bottom: 5, left: 0 }}
            >
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis
                dataKey="label"
                tick={{ fontSize: 11 }}
                tickLine={false}
              />
              <YAxis
                tick={{ fontSize: 11 }}
                tickLine={false}
                axisLine={false}
                tickFormatter={(v) => `${(v / 1000).toFixed(0)}K`}
              />
              <Tooltip
                formatter={(value) => [
                  `${formatNumber(Number(value) * rate, 2)} ${symbol}/MT`,
                  "Avg Price/MT",
                ]}
              />
              <Line
                type="monotone"
                dataKey="avg_price_per_mt"
                stroke={PRIMARY}
                strokeWidth={2}
                dot={{ fill: PRIMARY, r: 4 }}
                activeDot={{ r: 6 }}
              />
            </LineChart>
          </ResponsiveContainer>
        )}
      </ChartCard>

      {/* 5. Status Breakdown (Donut) */}
      <ChartCard title="Status Breakdown">
        {statusData.length === 0 ? (
          <EmptyState message="No estimations to display" />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie
                data={statusData}
                cx="50%"
                cy="50%"
                innerRadius={60}
                outerRadius={90}
                paddingAngle={3}
                dataKey="value"
                label={({ name, value }) => `${name} (${value})`}
                labelLine={false}
              >
                {statusData.map((entry, index) => (
                  <Cell key={`st-${index}`} fill={entry.color} />
                ))}
              </Pie>
              <Tooltip />
              <Legend />
            </PieChart>
          </ResponsiveContainer>
        )}
      </ChartCard>

      {/* 6. FCPBS Cost vs Selling Price */}
      <ChartCard title="Cost vs Selling Price by Category" span={2}>
        {data.cost_category_breakdown.length === 0 ? (
          <EmptyState message="No cost category data available" />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={data.cost_category_breakdown}
              margin={{ top: 5, right: 20, bottom: 5, left: 0 }}
            >
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis
                dataKey="name"
                tick={{ fontSize: 10 }}
                tickLine={false}
                angle={-30}
                textAnchor="end"
                height={60}
              />
              <YAxis
                tick={{ fontSize: 11 }}
                tickLine={false}
                axisLine={false}
                tickFormatter={compactNumber}
              />
              <Tooltip
                formatter={(value, name) => [
                  `${formatNumber(Number(value) * rate, 0)} ${symbol}`,
                  name,
                ]}
              />
              <Legend />
              <Bar
                dataKey="total_cost"
                name="Total Cost"
                fill={AMBER}
                radius={[4, 4, 0, 0]}
              />
              <Bar
                dataKey="total_selling"
                name="Selling Price"
                fill={PRIMARY}
                radius={[4, 4, 0, 0]}
              />
            </BarChart>
          </ResponsiveContainer>
        )}
      </ChartCard>
    </div>
  );
}
