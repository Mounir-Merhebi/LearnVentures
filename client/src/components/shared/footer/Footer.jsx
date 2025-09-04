import "./Footer.css";
import {
  FaFacebook,
  FaTwitter,
  FaInstagram,
  FaLinkedin,
  FaYoutube,
  FaGithub,
  FaMapMarkerAlt,
  FaPhone,
} from "react-icons/fa";
import { SiWhatsapp } from "react-icons/si";

const Footer = () => {
  return (
    <footer className="landing-footer">
      <div className="landing-footer-main">
        <div className="landing-footer-logo">
          <img src="/images/logoText.png" alt="LearnVentures Logo" />
        </div>
        <div className="landing-footer-links">
          <a href="#">HELP</a>
          <a href="#">ABOUT US</a>
          <a href="#">CONTACT US</a>
          <a href="#">PRIVACY POLICY</a>
        </div>
        <div className="landing-footer-info">
          <div className="landing-footer-location">
            <FaMapMarkerAlt />
            <span>Global Education Platform</span>
          </div>
          <div className="landing-footer-tel">
            <FaPhone style={{ transform: "rotateY(180deg)" }} />
            <span>+1 (555) 123-4567</span>
          </div>
          <div className="landing-footer-social">
            <span>Social Media</span>
            <div className="landing-footer-social-icons">
              <a href="#">
                <FaInstagram />
              </a>
              <a href="#">
                <FaFacebook />
              </a>
              <a href="#">
                <FaTwitter />
              </a>
              <a href="#">
                <FaLinkedin />
              </a>
              <a href="#">
                <FaYoutube />
              </a>
              <a href="#">
                <FaGithub />
              </a>
              <a href="#">
                <SiWhatsapp />
              </a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;