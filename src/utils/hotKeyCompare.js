export default (e, rec) =>
  rec && (e.key === rec.char) &&
  (rec.alt === undefined || e.altKey === rec.alt) &&
  (rec.ctrl === undefined || e.ctrlKey === rec.ctrl) &&
  (rec.shift === undefined || e.shiftKey === rec.shift);