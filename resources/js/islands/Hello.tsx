import React from 'react'

export function Hello({ where }: { where: string }) {
  return (
    <div className="p-4">
      <div className="rounded-2xl shadow p-4">
        <div className="text-sm uppercase tracking-wide">Island</div>
        <div className="text-2xl font-semibold">Hello from {where}</div>
      </div>
    </div>
  )
}

