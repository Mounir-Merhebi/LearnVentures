import "../../Auth/style.css";
import Button from "../../../Button/index.jsx";
import Input from "../../../Input";
import { useNavigate } from "react-router-dom";
import { useLoginForm } from "./logic.js";


const LoginForm = ({ toggle }) => {
  const navigate = useNavigate();
  const { email, password, errorMessage, handleFieldChange, loginUser } =
    useLoginForm();

  return (
    <div className="auth-body">
      <div className="my-logo-container">
        <img src="/images/Logo.png" alt="Logo" className="my-logo-img" />
        <h1>LEARNVENTURES</h1>
      </div>
      <div className="auth-container">
        <h1 className="auth-h1">
          <Button
            text={"←"}
            className="left-button"
            onClickListener={() => {
              navigate("/");
            }}
          />
          <span>Welcome Back</span>
        </h1>

        <form className="auth-form" onSubmit={loginUser}>
          <div>
            <label htmlFor="email" className="auth-label">
              Email
            </label>
            <Input
              type="email"
              name="email"
              hint="email@example.com"
              required={true}
              className="input-style"
              minLength={5}
              maxLength={100}
              value={email}
              onChangeListener={(e) =>
                handleFieldChange("email", e.target.value)
              }
            />
          </div>

          <div>
            <label htmlFor="password" className="auth-label">
              Password
            </label>
            <Input
              type="password"
              name="password"
              hint="************"
              required={true}
              className="input-style"
              minLength={8}
              maxLength={128}
              value={password}
              onChangeListener={(e) =>
                handleFieldChange("password", e.target.value)
              }
            />
          </div>

          {errorMessage && (
            <strong className="auth-error">{errorMessage}</strong>
          )}

          <Button
            text={"Login"}
            className="primary-button auth-button"
            onClickListener={loginUser}
          />
        </form>

        <strong className="auth-link">
          Don't have an account?{" "}
          <span className="auth-link-span" onClick={toggle}>
            Signup
          </span>
        </strong>
      </div>
    </div>
  );
};

export default LoginForm;
