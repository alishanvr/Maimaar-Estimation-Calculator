interface ChartCardProps {
    title: string;
    span?: 1 | 2;
    children: React.ReactNode;
}

export default function ChartCard({
    title,
    span = 1,
    children,
}: ChartCardProps) {
    return (
        <div
            className={`bg-white rounded-xl shadow-sm border border-gray-200 p-6 ${
                span === 2 ? 'md:col-span-2' : ''
            }`}
        >
            <h3 className="text-sm font-medium text-gray-500 mb-4">{title}</h3>
            <div className="h-64">{children}</div>
        </div>
    );
}
