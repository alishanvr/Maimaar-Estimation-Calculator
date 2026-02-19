import type { ReportKPIs } from '../../types/reports';
import { formatNumber } from '../../lib/formatters';
import { useCurrency } from '../../hooks/useCurrency';

interface KPICardsProps {
    kpis: ReportKPIs;
}

interface KPICardConfig {
    label: string;
    value: string;
    subtitle?: string;
}

export default function KPICards({ kpis }: KPICardsProps) {
    const { format, formatPerMT } = useCurrency();

    const cards: KPICardConfig[] = [
        {
            label: 'Total Estimations',
            value: formatNumber(kpis.total_estimations, 0),
            subtitle: `${kpis.finalized_count} finalized, ${kpis.calculated_count} calculated, ${kpis.draft_count} draft`,
        },
        {
            label: 'Total Weight',
            value: `${formatNumber(kpis.total_weight_mt, 2)} MT`,
        },
        {
            label: 'Total Revenue',
            value: format(kpis.total_revenue_aed, 0),
        },
        {
            label: 'Avg Price / MT',
            value: formatPerMT(kpis.avg_price_per_mt, 2),
        },
    ];

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {cards.map((card) => (
                <div
                    key={card.label}
                    className="bg-white rounded-xl shadow-sm border border-gray-200 p-5"
                >
                    <span className="text-xs font-medium text-gray-400 uppercase tracking-wide">
                        {card.label}
                    </span>
                    <p className="text-2xl font-bold text-gray-900 mt-1">
                        {card.value}
                    </p>
                    {card.subtitle && (
                        <p className="text-xs text-gray-500 mt-1">
                            {card.subtitle}
                        </p>
                    )}
                </div>
            ))}
        </div>
    );
}
