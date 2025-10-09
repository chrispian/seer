import React from 'react'

export function ApprovalButtonSimple({ requestId, riskScore, status, approvedAt, rejectedAt, onApprove, onReject }: any) {
  if (status === 'approved') {
    return (
      <div style={{ padding: '10px', border: '2px solid green', margin: '10px 0', background: '#f0fdf4' }}>
        <div>✓ Approved by user at {new Date(approvedAt).toLocaleTimeString()}</div>
      </div>
    )
  }

  if (status === 'rejected') {
    return (
      <div style={{ padding: '10px', border: '2px solid red', margin: '10px 0', background: '#fef2f2' }}>
        <div>✗ Rejected by user at {new Date(rejectedAt).toLocaleTimeString()}</div>
      </div>
    )
  }

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
