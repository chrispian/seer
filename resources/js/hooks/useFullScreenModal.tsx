export function useFullScreenModal(isOpen: boolean, onClose: () => void) {
  return {
    dialogProps: {
      open: isOpen,
      onOpenChange: onClose,
    },
    contentProps: {
      className: "max-w-[95vw] h-[90vh] p-0",
    },
  }
}
