import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { Presentation, PresentationFile } from "@oai/artifact-tool";
import { PDFDocument } from "pdf-lib";

const workspaceDir = "E:/__ย้ายจาก Desktop/project/salt1";
const skillDir = "C:/Users/ADMIN/.codex/plugins/cache/openai-primary-runtime/presentations/26.1026.10807/skills/presentations";
const buildDir = path.join(workspaceDir, ".build-sss-deck");
const outputDir = path.join(workspaceDir, "outputs");
const finalPptx = path.join(outputDir, "SSS_คู่มือระบบ_สรุป8สไลด์.pptx");
const finalPdf = path.join(outputDir, "SSS_คู่มือระบบ_สรุป8สไลด์.pdf");
const W = 1280;
const H = 720;
const FONT = "TH Sarabun New";
const C = {
  navy: "#162C45",
  navy2: "#233E5B",
  red: "#D81843",
  coral: "#F36B71",
  teal: "#188C8A",
  gold: "#D6A63B",
  ink: "#24364B",
  muted: "#617286",
  light: "#F4F7FA",
  line: "#D7DFE7",
  white: "#FFFFFF",
  green: "#27836B",
  orange: "#D18434",
};
const noLine = { style: "solid", fill: "none", width: 0 };

const { resolvePresentationFont, finalizePresentation } = await import(pathToFileURL(path.join(skillDir, "container_tools/artifact_tool_utils.mjs")).href);
resolvePresentationFont({ availableFonts: [FONT] });

const pres = Presentation.create({ slideSize: { width: W, height: H } });

function shape(slide, geometry, x, y, w, h, fill = "none", line = noLine, name) {
  return slide.shapes.add({
    geometry,
    ...(name ? { name } : {}),
    position: { left: x, top: y, width: w, height: h },
    fill,
    line,
  });
}

function txt(slide, text, x, y, w, h, opts = {}) {
  const s = shape(slide, "textbox", x, y, w, h, "none", noLine, opts.name);
  s.text = text;
  s.text.style = {
    typeface: FONT,
    fontSize: opts.size ?? 27,
    color: opts.color ?? C.ink,
    bold: opts.bold ?? false,
    alignment: opts.align ?? "left",
    verticalAlignment: opts.valign ?? "middle",
    autoFit: "shrinkText",
    wrap: "square",
    insets: 0,
  };
  return s;
}

function line(slide, x1, y1, x2, y2, color = C.line, width = 2) {
  const left = Math.min(x1, x2);
  const top = Math.min(y1, y2);
  const s = slide.shapes.add({
    geometry: "line",
    position: { left, top, width: Math.abs(x2 - x1), height: Math.abs(y2 - y1), horizontalFlip: x2 < x1, verticalFlip: y2 < y1 },
    fill: "none",
    line: { style: "solid", fill: color, width },
  });
  return s;
}

function circle(slide, x, y, d, fill, text, color = C.white, size = 26) {
  const s = shape(slide, "ellipse", x, y, d, d, fill, noLine);
  if (text !== undefined) {
    s.text = String(text);
    s.text.style = { typeface: FONT, fontSize: size, bold: true, color, alignment: "center", verticalAlignment: "middle", autoFit: "shrinkText", insets: 0 };
  }
  return s;
}

function roundRect(slide, x, y, w, h, fill, lineColor = "none", radius = 18) {
  const config = { geometry: "roundRect", position: { left: x, top: y, width: w, height: h }, fill, line: lineColor === "none" ? noLine : { style: "solid", fill: lineColor, width: 1 } };
  return slide.shapes.add({ ...config, borderRadius: radius });
}

function title(slide, heading, sub = "") {
  txt(slide, heading, 64, 42, 1148, 68, { size: 48, bold: true, color: C.navy });
  if (sub) txt(slide, sub, 66, 112, 1110, 38, { size: 23, color: C.muted });
}

function note(slide, prose, sources) {
  slide.speakerNotes.text = `${prose}\n\nแหล่งข้อมูลจากคู่มือ:\n${sources.map(s => `• ${s}`).join("\n")}`;
}

function addProvinceNames(slide, y, color = C.navy) {
  const names = ["อุบลราชธานี", "ศรีสะเกษ", "ยโสธร", "อำนาจเจริญ", "มุกดาหาร"];
  const xs = [66, 292, 500, 685, 891];
  const widths = [210, 190, 168, 200, 175];
  names.forEach((n, i) => txt(slide, n, xs[i], y, widths[i], 34, { size: 22, bold: true, color }));
}

function source(slide, name) {
  slide.speakerNotes.text = name;
}

// 1. Cover
{
  const s = pres.slides.add({ background: { fill: C.navy } });
  txt(s, "คู่มือระบบ", 82, 86, 520, 58, { size: 40, bold: true, color: "#F4BBC6" });
  txt(s, "Smart Salt &\nSodium Monitor", 78, 150, 680, 182, { size: 72, bold: true, color: C.white });
  txt(s, "สรุปการใช้งานและการอ่านข้อมูล\nเขตสุขภาพที่ 10", 84, 378, 560, 92, { size: 34, color: "#E7EDF4" });
  line(s, 86, 505, 1180, 505, "#456079", 2);
  txt(s, "แดชบอร์ดลดโซเดียม  ·  5 จังหวัด  ·  8 สไลด์", 86, 531, 800, 45, { size: 25, color: "#D2DCE7" });
  // Network diagram: one monitoring system connected to the five participating provinces.
  const nodes = [
    { x: 830, y: 88, label: "อุบลราชธานี" }, { x: 1080, y: 112, label: "ศรีสะเกษ" },
    { x: 1113, y: 318, label: "ยโสธร" }, { x: 984, y: 414, label: "อำนาจเจริญ" },
    { x: 810, y: 330, label: "มุกดาหาร" },
  ];
  for (const n of nodes) {
    line(s, 1002, 265, n.x + 24, n.y + 24, "#6A8093", 2);
    circle(s, n.x, n.y, 48, C.teal, undefined);
    txt(s, n.label, n.x - 28, n.y + 54, 150, 34, { size: 20, color: C.white, align: "center" });
  }
  circle(s, 942, 205, 120, C.red, "SSS", C.white, 34);
  note(s, "เปิดการนำเสนอด้วยภาพรวมว่า SSS รวบรวมข้อมูลด้านการลดโซเดียมของห้าจังหวัดในเขตสุขภาพที่ 10.", ["1-คู่มือการใช้งาน_หน้าหลัก.docx"]);
}

// 2. Access and account setup
{
  const s = pres.slides.add({ background: { fill: C.white } });
  title(s, "การเข้าถึงระบบ", "ผู้ชมทั่วไปเปิดดูภาพรวมได้ ส่วนเจ้าหน้าที่ใช้บัญชีเพื่อจัดการข้อมูล");
  txt(s, "ผู้ชมทั่วไป", 68, 190, 230, 42, { size: 30, bold: true, color: C.teal });
  txt(s, "เปิดหน้าหลักได้ทันที\nไม่ต้องเข้าสู่ระบบ", 68, 238, 270, 88, { size: 27, color: C.ink });
  line(s, 385, 175, 385, 350, C.line, 2);
  txt(s, "เจ้าหน้าที่", 438, 180, 220, 42, { size: 30, bold: true, color: C.red });
  const steps = [
    { x: 488, n: "01", title: "สมัครหรือเข้าสู่ระบบ", body: "ใช้บัญชีอีเมล\nและรหัสผ่าน" },
    { x: 744, n: "02", title: "ยืนยัน OTP", body: "รหัส 6 หลัก\nใช้ได้ 5 นาที*" },
    { x: 1000, n: "03", title: "เริ่มใช้งาน", body: "บัญชีใหม่รอ\nผู้ดูแลอนุมัติ" },
  ];
  line(s, 542, 276, 728, 276, C.line, 2);
  line(s, 798, 276, 984, 276, C.line, 2);
  for (let i = 0; i < steps.length; i++) {
    const st = steps[i];
    circle(s, st.x, 249, 54, i === 0 ? C.red : i === 1 ? C.orange : C.teal, st.n, C.white, 21);
    txt(s, st.title, st.x - 70, 316, 194, 40, { size: 24, bold: true, color: C.navy, align: "center" });
    txt(s, st.body, st.x - 64, 359, 182, 60, { size: 22, color: C.muted, align: "center" });
  }
  line(s, 68, 430, 1208, 430, C.line, 2);
  txt(s, "ลืมรหัสผ่าน", 68, 442, 220, 38, { size: 28, bold: true, color: C.navy });
  txt(s, "ขอรหัส OTP ทางอีเมลเพื่อยืนยันตัวตน แล้วตั้งรหัสผ่านใหม่", 300, 438, 820, 46, { size: 27, color: C.ink });
  txt(s, "* OTP สำหรับการเข้าสู่ระบบอาจขึ้นกับการตั้งค่าของระบบ", 68, 520, 900, 36, { size: 21, color: C.muted });
  note(s, "ผู้ชมทั่วไปอ่านหน้าสาธารณะได้โดยไม่เข้าสู่ระบบ เจ้าหน้าที่สมัครบัญชีด้วยอีเมลจริงและรอผู้ดูแลอนุมัติ หากมี OTP สำหรับเข้าสู่ระบบ รหัสมีอายุห้านาที ขั้นตอนลืมรหัสผ่านใช้ OTP และสร้างรหัสผ่านใหม่.", ["8-คู่มือการใช้งาน_หน้าเข้าสู่ระบบและสมัครสมาชิก_ฉบับสมบูรณ์.docx"]);
}

// 3. Home dashboard
{
  const s = pres.slides.add({ background: { fill: C.white } });
  title(s, "หน้าหลัก: ภาพรวมทั้งเขต", "เลือกปีและจังหวัด แล้วข้อมูลทุกส่วนจะปรับตามตัวกรอง");
  const screenshotPath = "C:/Users/ADMIN/AppData/Local/Temp/codex-clipboard-1ac2bd82-fb84-45fa-9b3f-aabd7de59528.png";
  try {
    const bytes = await fs.readFile(screenshotPath);
    const blob = bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength);
    s.images.add({ blob, contentType: "image/png", alt: "แถบเมนูระบบจากภาพที่ผู้ใช้แนบ", fit: "contain", position: { left: 66, top: 155, width: 1148, height: 62 } });
  } catch {
    txt(s, "หน้าหลัก   ความตระหนักรู้   เมนูลดโซเดียม   ผลิตภัณฑ์ลดโซเดียม", 70, 165, 1120, 42, { size: 22, color: C.muted });
  }
  txt(s, "ปีงบประมาณ", 72, 250, 190, 32, { size: 21, bold: true, color: C.muted });
  roundRect(s, 70, 286, 210, 48, C.light, C.line, 12);
  txt(s, "เลือกปี", 90, 288, 170, 44, { size: 25, color: C.navy });
  txt(s, "จังหวัด", 320, 250, 150, 32, { size: 21, bold: true, color: C.muted });
  roundRect(s, 318, 286, 245, 48, C.light, C.line, 12);
  txt(s, "ทุกจังหวัด / จังหวัดที่เลือก", 338, 288, 205, 44, { size: 22, color: C.navy });
  txt(s, "5 เรื่องที่ติดตาม", 70, 372, 255, 42, { size: 29, bold: true, color: C.navy });
  const modules = [
    "ความตระหนักรู้\nด้านโซเดียม", "พชอ.ไต\nผลดำเนินงาน", "เมนูลด\nโซเดียม", "อัตราป่วยใหม่\nความดัน HT", "ผลิตภัณฑ์\nลดโซเดียม",
  ];
  const mx = [70, 300, 530, 760, 990];
  modules.forEach((m, i) => {
    circle(s, mx[i], 444, 42, i === 0 ? C.red : i === 1 ? C.teal : C.navy2, `${i + 1}`, C.white, 21);
    txt(s, m, mx[i] + 54, 438, 170, 77, { size: 23, bold: true, color: C.ink });
    if (i < 4) line(s, mx[i] + 220, 437, mx[i] + 220, 521, C.line, 1);
  });
  txt(s, "พื้นที่ครอบคลุม", 70, 566, 205, 34, { size: 22, bold: true, color: C.teal });
  addProvinceNames(s, 603, C.ink);
  note(s, "หน้าหลักรวบรวมตัวชี้วัดห้าด้านไว้ในหน้าเดียว ตัวกรองปีและจังหวัดมีผลกับตัวเลขทุกส่วน แตะหรือคลิกหัวข้อสรุปเพื่อเลื่อนไปดูรายละเอียด.", ["1-คู่มือการใช้งาน_หน้าหลัก.docx"]);
}

// 4. Awareness
{
  const s = pres.slides.add({ background: { fill: C.white } });
  title(s, "ความตระหนักรู้ด้านโซเดียม", "อ่านผลผ่านเกณฑ์ตามปีที่เลือก และดูความครอบคลุมรายพื้นที่");
  const flow = [
    { x: 72, n: "01", head: "เลือกขอบเขต", body: "ปีงบประมาณ\nจังหวัดและอำเภอ" },
    { x: 470, n: "02", head: "ตรวจจำนวนผู้ตอบ", body: "ผ่านเกณฑ์\nไม่ผ่านเกณฑ์" },
    { x: 866, n: "03", head: "เปรียบเทียบพื้นที่", body: "แผนที่และสถานะ\nรายจังหวัด / อำเภอ" },
  ];
  flow.forEach((it, i) => {
    circle(s, it.x, 195, 54, i === 0 ? C.navy2 : i === 1 ? C.red : C.teal, it.n, C.white, 21);
    txt(s, it.head, it.x + 70, 192, 260, 38, { size: 28, bold: true, color: C.navy });
    txt(s, it.body, it.x + 70, 236, 260, 64, { size: 23, color: C.muted });
    if (i < 2) line(s, it.x + 54, 316, flow[i + 1].x - 18, 316, C.line, 2);
  });
  line(s, 68, 365, 1208, 365, C.line, 2);
  txt(s, "อัตราผ่านเกณฑ์", 70, 395, 270, 40, { size: 30, bold: true, color: C.red });
  txt(s, "จำนวนผู้ผ่าน  ÷  ผู้ตอบที่ผ่านและไม่ผ่านรวมกัน", 70, 442, 690, 42, { size: 26, color: C.ink });
  txt(s, "พฤติกรรมที่นับว่าทำได้ดี", 70, 515, 310, 40, { size: 26, bold: true, color: C.teal });
  const habits = [
    "ไม่เติมน้ำปลาหรือเครื่องปรุงเพิ่ม", "ไม่กินอาหารแปรรูป",
    "ไม่กินอาหารหมักดอง", "ลดจิ้ม ลดซด ลดเครื่องปรุงทุกครั้ง",
  ];
  habits.forEach((h, i) => {
    const x = i % 2 === 0 ? 70 : 655;
    const y = 563 + Math.floor(i / 2) * 50;
    circle(s, x, y + 2, 26, i === 0 || i === 3 ? C.teal : C.navy2, `${i + 1}`, C.white, 15);
    txt(s, h, x + 40, y - 2, 520, 36, { size: 22, color: C.ink });
  });
  txt(s, "หากปีนั้นยังไม่กำหนดเกณฑ์ ระบบจะแสดงสถานะรอเกณฑ์", 760, 421, 420, 54, { size: 21, color: C.muted });
  note(s, "อัตราผ่านคำนวณจากผู้ผ่านหารด้วยจำนวนผู้ตอบที่ผ่านและไม่ผ่านรวมกัน ผลขึ้นกับปี จังหวัด และอำเภอที่เลือก หากยังไม่มีเกณฑ์ของปีนั้น ระบบไม่คำนวณอัตราผ่าน. เกณฑ์พฤติกรรมสี่ข้อแสดงบนสไลด์.", ["2-คู่มือการใช้งาน_หน้าความตระหนักรู้.docx"]);
}

// 5. PHC kidney work
{
  const s = pres.slides.add({ background: { fill: C.white } });
  title(s, "พชอ.ไต: ความก้าวหน้าระดับพื้นที่", "ติดตามการรายงานและความครบถ้วนของ 8 ตัวชี้วัด");
  const indicators = [
    "ขับเคลื่อนงาน NCD ระดับอำเภอ", "จัดการข้อมูลเฝ้าระวัง", "กำหนดปัญหาและแผนงาน", "นโยบายสาธารณะ NCD / โรคไต",
    "สิ่งแวดล้อมเพื่อสุขภาพ", "ชุมชนลดปัจจัยเสี่ยง", "จัดบริการเชิงรุกในชุมชน", "ด้านโรคไต (8.1–8.3)",
  ];
  const xCols = [75, 665];
  indicators.forEach((v, i) => {
    const col = i < 4 ? 0 : 1;
    const row = i % 4;
    const x = xCols[col];
    const y = 184 + row * 95;
    circle(s, x, y, 44, row === 3 ? C.red : C.teal, `${i + 1}`, C.white, 21);
    txt(s, v, x + 62, y - 2, 470, 51, { size: 24, bold: i === 7, color: C.ink });
    if (row < 3) line(s, x + 22, y + 47, x + 22, y + 85, C.line, 2);
  });
  line(s, 632, 174, 632, 566, C.line, 2);
  roundRect(s, 74, 584, 1135, 78, C.light, "none", 14);
  txt(s, "อ่านผล", 96, 600, 110, 40, { size: 25, bold: true, color: C.red });
  txt(s, "ดูอำเภอที่รายงาน  ·  เช็กหน่วยงานที่ทำครบ 8 ข้อ  ·  ทบทวนตัวชี้วัดที่ยังไม่มีพื้นที่ทำ", 218, 597, 960, 50, { size: 23, color: C.ink });
  txt(s, "สำหรับข้อ 8 ทำข้อย่อย 8.1–8.3 อย่างน้อยหนึ่งข้อ ก็ถือว่าทำตัวชี้วัดข้อ 8 แล้ว", 76, 667, 1120, 30, { size: 20, color: C.muted });
  note(s, "หน้าพชอ.ไตแสดงการรายงานระดับพื้นที่ ความครอบคลุมอำเภอ ความครบถ้วนของหน่วยงาน ตัวชี้วัดที่ทำได้ดี และประเด็นที่ยังไม่มีหน่วยงานทำ ข้อ 8 นับว่าทำแล้วเมื่อทำข้อย่อยใดข้อย่อยหนึ่งใน 8.1–8.3.", ["6-คู่มือการใช้งาน_หน้าผลการดำเนินงาน_พชอ.ไต.docx"]);
}

// 6. Menus and products
{
  const s = pres.slides.add({ background: { fill: C.white } });
  title(s, "เมนูและผลิตภัณฑ์ลดโซเดียม", "สองมุมมองของการปรับสูตรและการรับรองผลิตภัณฑ์");
  txt(s, "เมนูอาหาร", 72, 176, 300, 42, { size: 31, bold: true, color: C.red });
  txt(s, "ประเมินจากโซเดียมหลังปรับสูตร", 72, 218, 470, 34, { size: 22, color: C.muted });
  const bands = [
    { y: 278, fill: "#DFF0E8", level: "ดีมาก", range: "น้อยกว่า 600 มก.", color: C.green },
    { y: 348, fill: "#E7F0F2", level: "ดี", range: "600–800 มก.", color: C.teal },
    { y: 418, fill: "#FFF0D6", level: "ปานกลาง", range: "801–1,000 มก.", color: C.orange },
    { y: 488, fill: "#FCE3E7", level: "ควรปรับปรุง", range: "มากกว่า 1,000 มก.", color: C.red },
  ];
  bands.forEach(b => {
    roundRect(s, 72, b.y, 472, 56, b.fill, "none", 10);
    txt(s, b.level, 92, b.y + 2, 178, 49, { size: 25, bold: true, color: b.color });
    txt(s, b.range, 286, b.y + 2, 232, 49, { size: 24, color: C.ink });
  });
  line(s, 605, 172, 605, 573, C.line, 2);
  txt(s, "ผลิตภัณฑ์", 654, 176, 285, 42, { size: 31, bold: true, color: C.teal });
  txt(s, "ดูภาพรวมตามตัวกรอง", 654, 218, 430, 34, { size: 22, color: C.muted });
  const prod = [
    { y: 295, n: "01", head: "จำนวนผลิตภัณฑ์", body: "นับรายการตามปีและพื้นที่" },
    { y: 385, n: "02", head: "การรับรองมาตรฐาน", body: "ดูสัดส่วนที่มีการรับรอง" },
    { y: 475, n: "03", head: "ความครอบคลุมจังหวัด", body: "ดูพื้นที่และประเภทผลิตภัณฑ์" },
  ];
  prod.forEach((p, i) => {
    circle(s, 654, p.y, 42, i === 1 ? C.red : C.navy2, p.n, C.white, 17);
    txt(s, p.head, 710, p.y - 3, 398, 35, { size: 25, bold: true, color: C.ink });
    txt(s, p.body, 710, p.y + 34, 430, 36, { size: 22, color: C.muted });
  });
  roundRect(s, 656, 565, 530, 57, C.light, "none", 12);
  txt(s, "ยังไม่ได้วัดหลังปรับสูตร: ไม่นำไปคิดค่าเฉลี่ยหรือระดับเมนู", 674, 572, 496, 42, { size: 21, bold: true, color: C.navy });
  note(s, "หน้ารายงานเมนูจัดระดับจากผลโซเดียมหลังปรับสูตรตามเกณฑ์ที่แสดง เมนูที่ยังไม่มีผลวัดหลังปรับสูตรยังรวมในจำนวนเมนูทั้งหมด แต่ไม่นำไปคำนวณค่าเฉลี่ย ร้อยละที่ลดลง หรือระดับประเมิน หน้าผลิตภัณฑ์แยกดูจำนวน การรับรอง และพื้นที่ครอบคลุม.", ["3-คู่มือการใช้งาน_หน้าเมนูลดโซเดียม.docx", "4-คู่มือการใช้งาน_หน้าผลิตภัณฑ์ลดโซเดียม.docx"]);
}

// 7. New HT cases
{
  const s = pres.slides.add({ background: { fill: C.white } });
  title(s, "อัตราป่วยรายใหม่ความดันโลหิตสูง (HT)", "ติดตามจำนวนผู้ป่วยใหม่และอัตราต่อแสนประชากร");
  txt(s, "วิธีอ่านตัวเลข", 72, 185, 270, 42, { size: 30, bold: true, color: C.navy });
  roundRect(s, 72, 244, 475, 178, C.light, "none", 18);
  txt(s, "ผู้ป่วยรายใหม่", 102, 270, 414, 43, { size: 27, bold: true, color: C.red, align: "center" });
  line(s, 132, 327, 487, 327, C.navy2, 2);
  txt(s, "ประชากรเป้าหมาย", 102, 336, 414, 36, { size: 24, color: C.ink, align: "center" });
  txt(s, "คูณ 100,000 = อัตราต่อแสน", 110, 381, 400, 32, { size: 23, bold: true, color: C.teal, align: "center" });
  txt(s, "ข้อมูลแยกตาม", 72, 462, 185, 35, { size: 23, bold: true, color: C.muted });
  txt(s, "จังหวัด  ·  อำเภอ  ·  เดือน  ·  ปีงบประมาณ", 264, 460, 560, 38, { size: 24, color: C.ink });
  line(s, 635, 180, 635, 586, C.line, 2);
  txt(s, "จุดที่ควรระวัง", 684, 185, 300, 42, { size: 30, bold: true, color: C.red });
  const cautions = [
    "อัตราเฉลี่ยเขตคำนวณจากยอดผู้ป่วยรวม ÷ ประชากรรวม ไม่ใช่ค่าเฉลี่ยอัตรารายจังหวัด",
    "พื้นที่ที่ไม่มีประชากรเป้าหมายจะไม่ร่วมในทั้งตัวตั้งและตัวหาร",
    "เดือนกันยายนเป็นเดือนสุดท้ายของปีงบประมาณ หากปียังไม่ครบ ให้ดูเดือนล่าสุดที่มีข้อมูลในกราฟ",
  ];
  cautions.forEach((t, i) => {
    circle(s, 684, 250 + i * 106, 32, i === 2 ? C.orange : C.navy2, `${i + 1}`, C.white, 17);
    txt(s, t, 730, 246 + i * 106, 460, 82, { size: 22, color: C.ink });
  });
  note(s, "รายงานแสดงยอดผู้ป่วยรายใหม่และอัตราต่อประชากรหนึ่งแสนคน แยกพื้นที่และแนวโน้มรายเดือน การคำนวณอัตรารวมใช้ยอดผู้ป่วยและประชากรรวมของพื้นที่ที่มีเป้าหมาย ไม่ใช้ค่าเฉลี่ยของอัตรารายจังหวัด เดือนกันยายนเป็นเดือนสุดท้ายของปีงบประมาณ.", ["5-คู่มือการใช้งาน_หน้าอัตราป่วยรายใหม่_HT.docx"]);
}

// 8. Salt consumption report and action
{
  const s = pres.slides.add({ background: { fill: C.white } });
  title(s, "รายงานลดการบริโภคเกลือ", "ติดตามความคืบหน้ารายหน่วยงานแบบสะสมตามไตรมาส");
  const stages = ["ขั้นที่ 1", "ขั้นที่ 2", "ขั้นที่ 3", "ขั้นที่ 4", "ขั้นที่ 5"];
  const sx = [78, 306, 534, 762, 990];
  stages.forEach((st, i) => {
    circle(s, sx[i], 202, 58, i === 4 ? C.red : i === 0 ? C.teal : C.navy2, `${i + 1}`, C.white, 25);
    txt(s, st, sx[i] - 10, 269, 150, 40, { size: 25, bold: true, color: C.navy, align: "center" });
    if (i < stages.length - 1) line(s, sx[i] + 58, 231, sx[i + 1] - 12, 231, C.line, 3);
  });
  roundRect(s, 990, 326, 194, 50, "#FCE3E7", "none", 11);
  txt(s, "5.1  5.2  5.3  5.4  5.5", 1000, 332, 174, 36, { size: 20, bold: true, color: C.red, align: "center" });
  txt(s, "ตัวกรองไตรมาสนับสะสมตั้งแต่ต้นปีถึงไตรมาสที่เลือก", 94, 360, 825, 43, { size: 24, color: C.ink });
  line(s, 68, 425, 1208, 425, C.line, 2);
  const rules = [
    { x: 72, head: "มุมมองสรุป", body: "ขั้นที่ 5 ถือว่าทำแล้ว\nเมื่อทำข้อย่อยใดข้อหนึ่ง" },
    { x: 462, head: "ตารางรายละเอียด", body: "สถานะส่งครบแล้ว\nต้องครบทั้ง 9 รายการ" },
    { x: 852, head: "การติดตาม", body: "คลิกหน่วยงานเพื่ออ่าน\nผล ปัญหา และข้อเสนอแนะ" },
  ];
  rules.forEach((r, i) => {
    txt(s, r.head, r.x, 457, 320, 40, { size: 27, bold: true, color: i === 1 ? C.red : C.teal });
    txt(s, r.body, r.x, 503, 320, 70, { size: 23, color: C.ink });
    if (i < 2) line(s, r.x + 340, 454, r.x + 340, 580, C.line, 1);
  });
  roundRect(s, 72, 610, 1116, 55, C.navy, "none", 14);
  txt(s, "วิธีใช้ข้อมูล: เลือกตัวกรอง แล้วเทียบพื้นที่ เปิดรายละเอียด และติดตามงานที่ยังขาด", 94, 616, 1070, 41, { size: 23, bold: true, color: C.white });
  note(s, "รายงานติดตามห้าขั้นตอน โดยขั้นที่ห้าแยกเป็นห้ารายการย่อย รวมเก้ารายการในตาราง ไตรมาสที่เลือกเป็นยอดสะสมตั้งแต่ต้นปี มุมมองสรุปนับขั้นที่ห้าว่าทำแล้วหากมีข้อย่อยใดข้อหนึ่ง แต่สถานะส่งครบแล้วในตารางต้องครบทั้งเก้ารายการ.", ["7-คู่มือการใช้งาน_หน้าแบบรายงานลดการบริโภคเกลือ.docx"]);
}

// Render editable deck, export PDF, then finalize PPTX into the requested output folder.
await fs.mkdir(buildDir, { recursive: true });
await fs.mkdir(outputDir, { recursive: true });
for (let i = 0; i < pres.slides.items.length; i++) {
  const slide = pres.slides.items[i];
  const png = await pres.export({ slide, format: "png", scale: 1 });
  await fs.writeFile(path.join(buildDir, `slide-${String(i + 1).padStart(2, "0")}.png`), new Uint8Array(await png.arrayBuffer()));
}
const pdf = await PDFDocument.create();
for (let i = 0; i < pres.slides.items.length; i++) {
  const pngBytes = await fs.readFile(path.join(buildDir, `slide-${String(i + 1).padStart(2, "0")}.png`));
  const image = await pdf.embedPng(pngBytes);
  const page = pdf.addPage([W, H]);
  page.drawImage(image, { x: 0, y: 0, width: W, height: H });
}
await fs.writeFile(finalPdf, await pdf.save());
const candidatePath = path.join(buildDir, "candidate.pptx");
const draft = await PresentationFile.exportPptx(pres);
await draft.save(candidatePath);

const validated = await finalizePresentation({
  workspaceDir,
  candidatePath,
  finalPath: finalPptx,
  pythonExecutable: "C:/Users/ADMIN/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe",
  integrityValidatorPath: path.join(skillDir, "container_tools/inspect_presentation_package_integrity.py"),
  layoutValidatorPath: path.join(skillDir, "container_tools/inspect_presentation_layout_geometry.py"),
  layoutArgs: ["--expected-slide-size-emu", "12192000,6858000", "--validate-bullet-geometry", "--validate-heading-fit"],
  requiredNativeTableOwnerSlides: [],
  requiredNativeChartOwnerSlides: [],
  explicitTotalSlideCount: 8,
  fontPolicy: { basis: "design", families: [FONT] },
  verifyArtifactToolImport: true,
  receiptPath: path.join(buildDir, "SSS_8สไลด์.validation-final.json"),
});
console.log(JSON.stringify({ pptx: finalPptx, pdf: finalPdf, slideCount: pres.slides.items.length, validation: validated }, null, 2));
