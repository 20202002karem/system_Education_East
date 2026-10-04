import type { ReactNode } from 'react';

interface Column<T> { header: string; render: (row: T) => ReactNode; hideOnMobile?: boolean; }

/**
 * Responsive table: horizontal scroll on narrow viewports (instructions §14)
 * rather than reflowing to cards, since M1 tables have few enough columns
 * that a scroll container keeps the data legible without extra components.
 */
export function Table<T extends { id: number | string }>({ columns, rows }: { columns: Column<T>[]; rows: T[] }) {
  return (
    <div className="table-scroll">
      <table>
        <thead>
          <tr>
            {columns.map((c, i) => (
              <th key={i} className={c.hideOnMobile ? 'hide-on-mobile' : undefined} style={{ textAlign: 'start', padding: 'var(--space-3)', borderBottom: '2px solid var(--color-border)', fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)' }}>
                {c.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              {columns.map((c, i) => (
                <td key={i} className={c.hideOnMobile ? 'hide-on-mobile' : undefined} style={{ padding: 'var(--space-3)', borderBottom: '1px solid var(--color-border)' }}>
                  {c.render(row)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
