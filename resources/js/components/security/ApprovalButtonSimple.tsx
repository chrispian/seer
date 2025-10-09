import React from 'react'

export function ApprovalButtonSimple({ requestId, riskScore, onApprove, onReject }: any) {
  return (
    <div style={{ padding: '10px', border: '2px solid orange', margin: '10px 0' }}>
      <div>Approval Request ID: {requestId}</div>
      <div>Risk Score: {riskScore}</div>
      <button onClick={onApprove} style={{ padding: '5px 10px', margin: '5px', background: 'green', color: 'white' }}>
        Approve
      </button>
      <button onClick={onReject} style={{ padding: '5px 10px', margin: '5px', background: 'red', color: 'white' }}>
        Reject
      </button>
    </div>
  )
}
